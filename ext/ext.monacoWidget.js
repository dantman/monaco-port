/**
 * Monaco Widget Framework 2.0
 * Replaces old obsolete YUI-based WidgetFramework plugin with frontend-driven
 * dynamic content. Widgets can be defined through the wiki frontend via use
 * of Extension:Gadgets.
 * Added to ResourceLoader as ext.monacoWidget
 *
 * @file
 * @ingroup Skins
 * @author James Haley
 * @copyright © 2014 James Haley
 * @license GNU General Public License 2.0 or later
 *
 */

(function ($, mw) {
  //
  // monacoWidget Constructor
  //
  // id         : HTML id of the widget. Must be globally unique.
  // title      : Title of the widget, displayed in a color1 style div with bold text by default.
  // priority   : If true, widget is inserted at the top of its location (but never above the Monaco navbar).
  // location   : Component serving as attachment point. If null, #widget_sidebar is used.
  // dynamic    : Content is dynamically loaded via AJAX, and a control icon is placed in the title bar.
  // automatic  : if non-zero, a click event will be synthesized after the given delay.
  // iconTitle  : Rollover text for dynamic widget icon
  // iconClass  : CSS sprite subclass for icon
  // contentCB  : Called after all other construction to add custom content immediately.
  // iconAction : Click callback function for dynamic content loading.
  //
  mw.libs.monacoWidget = function (id, title, priority, location, dynamic, automatic, iconTitle, iconClass, contentCB, iconAction) {
    // Some defaults
    if(!location)
      location = '#widget_sidebar';
      
    if($('#' + id).length !== 0)
      throw "This widget already exists."; // do not construct duplicate widgets
      
    this.id         = id;
    this.bar        = $(location); // reference to attachment location
    this.title      = title;
    this.dynamic    = dynamic;
    this.iconTitle  = iconTitle;
    this.iconClass  = iconClass;
    this.iconAction = iconAction;
    
    // 
    // Methods
    //

    // Private, creates a composite HTML entity ID for a widget subcomponent
    var makeCompositeID = function (widgetID, componentID) {
      return widgetID + '_' + componentID;
    };

    // Private, creates an AJAX icon button in the widget's title bar
    var makeAjaxToggleDiv = function (widget) {
      var ajaxID = makeCompositeID(widget.id, 'ajax');
      widget.ajaxPic = $('<div id="' + ajaxID + '" ' + 
                         'class="monacoWidgetAjaxToggle sprite" />');
      if(widget.iconTitle)
        widget.ajaxPic.attr('title', widget.iconTitle);
      if(widget.iconClass)
        widget.ajaxPic.addClass(widget.iconClass);
      if(widget.iconAction) {
        var fn = widget.iconAction;
        widget.ajaxPic.on('click', function () { fn(widget); });
      }
      return widget.ajaxPic;
    };

    // Private, creates the widget's title bar
    var makeTitleBar = function (widget) {
      var titleID = makeCompositeID(widget.id, 'title');
      widget.titleBar = $('<h3 id="' + titleID + '" class="color1 sidebox_title">' + widget.title + '</h3>');
      if(widget.dynamic)
        widget.titleBar.append(makeAjaxToggleDiv(widget));
      return widget.titleBar;
    };
  
    // Private, creates the widget's content area
    var makeContentArea = function (widget) {
      var contentID = makeCompositeID(widget.id, 'content');
      widget.content = $('<div id="' + contentID + '" ' + 
                       'class="sidebox_contents monacoWidgetContentArea" />');
      return widget.content;
    };

    // Private, creates the main widget body
    var makeWidgetBody = function (widget) {
      widget.body = $('<div id="' + widget.id + '" class="widget sidebox" />');
      widget.body.append(makeTitleBar(widget), makeContentArea(widget));
      return widget.body;
    };
    
    // Icon methods
    
    // Start AJAX loading animation
    this.doAjaxAnimation = function () {
      if(this.ajaxPic)
        this.ajaxPic.removeClass(this.iconClass).addClass('progress').attr('title', 'Loading...').css('cursor', 'wait');
    };
    
    // Stop AJAX loading animation
    this.stopAjaxAnimation = function () {
      if(this.ajaxPic)
        this.ajaxPic.removeClass('progress').addClass(this.iconClass).attr('title', this.iconTitle).css('cursor', 'pointer');
    };
    
    // Turn the icon into a Refresh button
    this.makeRefreshPic = function () {
      if(this.ajaxPic)
        this.ajaxPic.removeClass('progress').addClass('refresh').attr('title', 'Refresh').css('cursor', 'pointer');
    };
    
    // Content methods
    
    // Add a content element
    this.addContentElement = function (elem, limitSize) {
      var newElem = $(elem);
      if(limitSize)
        newElem.addClass('widget_contents'); // hidden overflow x, auto overflow y, 250px max height      
      this.content.append(newElem);
      return newElem;
    };
    
    // Add a standard list content element
    this.addStdListContentElement = function (limitSize) {
      return this.addContentElement(
        $('<ul id="' + makeCompositeID(this.id, 'stdlist') + '" class="monacoWidgetStdList" />'), 
        limitSize);
    };
    
    // Empty the standard list content element
    this.emptyStdListContentElement = function () {
      $('#' + makeCompositeID(this.id, 'stdlist')).empty();
    };
   
    // Construct the bar on the page
    if(priority) {
      if(location === '#widget_sidebar') // for left sidebar, add after navigation widget
        makeWidgetBody(this).insertAfter('#navigation_widget');
      else
        this.bar.prepend(makeWidgetBody(this)); // add at beginning
    }
    else
      this.bar.append(makeWidgetBody(this)); // add at end
      
    // Add custom content via callback if requested
    if(contentCB)
      contentCB(this);
      
    // If this widget is dynamic and automatic is true, auto-click it after the interval.
    if(this.ajaxPic && automatic) {
      var cWidget = this;
      window.setTimeout(function () { cWidget.ajaxPic.click(); }, automatic);
    }
  };
  
  //
  // Statics
  //
  
  // Manage widgets
  mw.libs.monacoWidget.Exists = function (id) {
    return ($('#' + id).length !== 0);
  };
  
  mw.libs.monacoWidget.IsMonaco = function () {
    return (mw.config.get('skin') === 'monaco');
  };
  
  // Standard query 1: Top 7 Recent changes
  mw.libs.monacoWidget.GetRecentChangesQuery = function () {
    return ({
      action:  'query',
      list:    'recentchanges',
      rctype:  'edit|new',
      rcshow:  '!bot',
      rcprop:  'user|title|timestamp|ids',
      rclimit: '7'
    });
  };
  
  // Perform an API query and execute the given callback with the data
  // resulting from the API query. Asynchronous (AJAX).
  mw.libs.monacoWidget.DoAPIQuery = function (widget, query, callback) {
    (new mw.Api()).get(query).done(function (data) { callback(widget, data); });
  };
  
  // Create a link to an article
  mw.libs.monacoWidget.ArticleLink = function (title, revs) {
    var href = mw.util.getUrl(title);
    if(revs)
      href += '?curid=' + revs.pageid + '&diff=' + revs.revid + '&oldid=' + revs.old_revid;
    return '<a href="' + href + '">' + title + '</a>';
  };
  
  // Create a link to a user page
  mw.libs.monacoWidget.UserLink = function (user) {
    return '<a href="' + mw.util.getUrl('User:' + user) + '">' + user + '</a>';
  };
  
  /*
   * JavaScript Pretty Date
   * Copyright (c) 2011 John Resig (ejohn.org)
   * Licensed under the MIT and GPL licenses.
   */

   // Takes an ISO time and returns a string representing how long ago the date represents.
  mw.libs.monacoWidget.PrettyDate = function (time) {
    var date = new Date((time || "")),
      diff = (((new Date()).getTime() - date.getTime()) / 1000),
      day_diff = Math.floor(diff / 86400);
      
    if(isNaN(day_diff) || day_diff >= 31)
      return;
    
    if(day_diff < 0) // possible when there's time drift between server and client
      return "just now";
    
    return day_diff == 0 && 
      (diff < 60 && "just now" ||
       diff < 120 && "1 minute ago" ||
       diff < 3600 && Math.floor( diff / 60 ) + " minutes ago" ||
       diff < 7200 && "1 hour ago" ||
       diff < 86400 && Math.floor( diff / 3600 ) + " hours ago") ||
      day_diff == 1 && "Yesterday" ||
      day_diff < 7 && day_diff + " days ago" ||
      day_diff < 31 && Math.ceil( day_diff / 7 ) + " weeks ago";
  };
})(jQuery, mediaWiki);