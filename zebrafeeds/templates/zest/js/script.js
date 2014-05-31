var zfReadBox = {
    overlay: 'zfOverlay',
    container: '#zffixednews',
    currentItem: '',
    nextItem: '',
    prevItem: '',
    init: function(feedurl, itemid, obj) {
        this.currentItem = $(obj).parent().parent();
        this.nextItem = this.currentItem.next();
        this.prevItem = this.currentItem.prev();
        
        var domElem = $('#' + this.overlay);
        if (domElem.length == 0) {
            $('#wrapper').prepend('<div id="' + this.overlay + '"></div>');
            // $('#wrapper').css('overflow', 'hidden');
        }
        this.loadContent(feedurl, itemid, obj);
        
        // events
        this.bindEvents(obj);
    },
    loadContent: function(feedurl, itemid) {
        var params = "type=jsonitem&xmlurl=" + escape(feedurl) + "&itemid=" + itemid;
        var me = this;
        $.get(ZFURL + "/async.php", params, function(data, textStatus, jqXHR) {
            me.displayContent(data);
            me.currentItem.addClass('read');
        });
    },
    displayContent: function(content, obj) {
        if ($(this.container).length == 0) {
            $('#zfOverlay').addClass('ontop').addClass('shown').after(container);
        }
        $('#zffixednews #newsContent').html(content);
        $('#wrapper > section').addClass('reading');
    },
    bindEvents: function(obj) {
        var me = this;
        
        $(document).off('click', '#zffixednews a.btn-close');
        $(document).on('click', '#zffixednews a.btn-close', function(e) {
            e.preventDefault();
            me.close();
        });
        $(window).off('keydown');
        $(window).on('keydown', function(e) {
            // e.preventDefault();
            // ESCAPE key pressed
            if (e.keyCode == 27) {
                me.close();
            }
            // right arrow key pressed
            if (e.keyCode == 39) {
                me.readNext();
            }
            // left arrow key pressed
            if (e.keyCode == 37) {
                me.readPrev();
            }
        });
        
    },
    readNext: function() {
        if (this.nextItem.length != 0) {
            var feedInfo = this.nextItem.attr('rel').split(';');
            this.currentItem = this.nextItem;
            var data = this.loadContent(feedInfo[0], feedInfo[1], this.nextItem);
            this.prevItem = this.nextItem.prev();
            this.nextItem = this.nextItem.next();
        }
    },
    readPrev: function() {
        if (this.prevItem.length != 0) {
            var feedInfo = this.prevItem.attr('rel').split(';');
            this.currentItem = this.prevItem;
            var data = this.loadContent(feedInfo[0], feedInfo[1]);
            this.nextItem = this.prevItem.next();
            this.prevItem = this.prevItem.prev();
        }
    },
    close: function() {
        var overlay = $('#' + this.overlay);
        overlay.removeClass('shown').removeClass('ontop');
        $('#wrapper > section').removeClass('reading');
    }
};


function Zest() {
    this.overlay    = $('#zfOverlay');
    this.readbox    = zfReadBox;
    this.zebrabar   = $('#zebrabar');
    this.feedsbox   = $('#content .main');
    this.zf_api_url = 'newsfeeds/async.php';
    
    this.zf_all_channels = {};
    this.init();
}

Zest.prototype = {
    
    init: function() {
        var self = this;
        this.feedsbox.on('click', '.itemtitle a, .newstitle a', function(e) {
            e.preventDefault();
            var id = $(this).attr('id');
            var openbox = $(this).data('openbox');
            var url = $(this).attr('href');
            self.showItem(url, id, this, openbox);
            
        });
        $('.titlebar').on('click', 'a.btn-menu', function(e) {
            e.preventDefault();
            self.showZebrabar();
        });
        $('#wrapper').on('click', '#zfOverlay', function(e) {
            e.preventDefault(e);
            self.hideZebrabar();
        });
        $('.main').on('scroll', function(e) {
            console.log('scroll');
        // $('.feed, #newsContent').on('scroll', function(e) {
            var offset = $(this).children('div').offset().top;
            var header_h = $(this).parent().children('header').outerHeight();
            if (offset < header_h) {
                if (!$('#content > header').hasClass('flying')) {
                    $('#content > header').addClass('flying');
                }
            } else {
                $('#content > header').removeClass('flying');
            }
        });
        this.loadZebrabar();
    },
    loadZebrabar: function() {
        this.bindZebrabarEvents();
        var self = this;
        $.ajax({
            url: self.zf_api_url,
            data: { type: 'listswithchannels' },
            type: 'get',
            dataType: 'JSON'
        }).done(function(rsp) {
            if (rsp) {
                self.zf_all_channels = rsp;
                var catslist = $('<ul></ul>');

                // categories
                $.each(rsp, function(i, categ) {
                    var li = $('<li></li>');
                    var link = $('<a></a>')
                                .attr('href', 'index.php?zflist=' + i)
                                .text(i);
                    var expander = $('<span class="expandr zf-icon">›</span>');
                    var channelslist = $('<ul></ul>').addClass('channels');
                    $.each(categ, function(j, channel) {
                        var channelitem = $('<li></li>');
                        var channellink = $('<a></a>');
                        channellink.attr('href', channel.channel.xmlurl)
                                    .text(channel.channel.title);
                        channellink.appendTo(channelitem);
                        channellink.data('zfposition', channel.position);
                        channelitem.appendTo(channelslist);
                    });


                    expander.appendTo(li);
                    link.appendTo(li);
                    channelslist.appendTo(li);
                    li.appendTo(catslist);
                });
                self.zebrabar.append(catslist);
            }
        });
    },
    bindZebrabarEvents: function() {
        var self = this;
        self.zebrabar.on('click', '.expandr', function() {
            var categitem = $(this).parent();
            var channelslist = categitem.children('ul');
            if (categitem.hasClass('active')) {
                $(this).parent().removeClass('active');
                channelslist.slideUp();
            } else {
                $(this).parent().addClass('active');
                channelslist.slideDown();
            }
        });
        // view category channels
        self.zebrabar.on('click', 'ul:not(.channels) > li > a', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var title = $(this).text();
            var categ = href.substring(href.lastIndexOf('=') + 1, href.length);
            var channels = self.zf_all_channels[categ];
            self.setPageTitle(title);
            self.feedsbox.html('');
            self.hideZebrabar();
            $.each(channels, function(i, item) {
                $('#content .main').append(
                    '<div class="zfchannelbox loading" id="tmp-' + i + '">'+
                        '<div class="channel">'+
                            '<div class="chanlink">' + 
                                '<a href="' + item.htmlurl + '">' + item.title + '</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );
            });

            $.each(channels, function(i, item) {
                $.ajax({
                    url: self.zf_api_url, //channelallitems
                    type: 'GET',
                    data: { 
                        type: 'channel',
                        xmlurl: item.channel.xmlurl,
                        maxitems: item.shownItems,
                        pos: item.position
                    }
                }).done(function(rsp) {
                    console.log(rsp);
                    var html = rsp;
                    $('#tmp-' + i).remove();
                    console.log(self.feedsbox);
                    self.feedsbox.append(html);
                }).fail(function() {
                    console.log('error');
                });
            });
        });
        
        this.zebrabar.on('click', '.channels a', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var title = $(this).text();
            self.feedsbox.html('');
            self.hideZebrabar();
            var position = $(this).data('zfposition');
            // gets items of channel
            $.ajax({
                url: self.zf_api_url,
                data: { 
                    type: 'jsonchannelallitems',
                    xmlurl: href,
                    pos: position
                },
                type: 'get',
                dataType: "JSON"
            }).done(function(rsp) {
                self.showChannelItems(rsp);
                self.setPageTitle(title);
            });
        });
    },
    showChannelItems: function(rsp) {
        var nb_pages = Math.floor(rsp.length / items_per_page);
        var items_per_page = 5;
        var self = this;
        if (rsp.length > 0) {
            var j = 1,
                pages = [];
            var page_i = [];
            $.each(rsp, function(i, item) {
                page_i.push(item);
                if (j % 5 == 0 || j == rsp.length) {
                    pages.push(page_i);
                    page_i = [];
                }
                j++;
            });
            var html_pages = [];
            
            $.each(pages, function(i, page) {
                var layout = new ZSTLayout(page);
                var page_div = layout.getHtml();
                page_div.css('left', '100%');
                self.feedsbox.append(page_div);
                $('iframe', self.feedsbox).remove();
            });
            $('.page').first().css('left', 0).addClass('infocus');
            
            $('.page').on('swipeleft', function() {
               console.log('swipeleft');
               if (!$(this).is($('.page:last'))) {
                    $(this)
                        .addClass('outfocus outfocus-left')
                        .removeClass('infocus');
                    $(this).next()
                        .removeClass('outfocus outfocus-right')
                        .addClass('infocus');
                }
            }).on('swiperight', function() {
                console.log('swiperight');
                if (!$(this).is($('.page:first'))) {
                    $(this)
                        .addClass('outfocus outfocus-right')
                        .removeClass('infocus');
                    $(this).prev()
                        .removeClass('outfocus outfocus-left')
                        .addClass('infocus');
                }
            });
        }
    },
    setPageTitle: function(new_title) {
        var top = $('#content > header .titlebar');
        if (top.children('h1').length == 0) {
            var h1 = $('<h1>').text(new_title);
            top.append(h1);
        } else {
            $('h1', top).text(new_title);
        }
    },
    /* all in one: fetch and show
    itemid is the zfeeder news item id, not the html element id
    outputid is the id of the element to send the output to. 
    the server know what to do...
    */
    showItem: function(feedurl, itemid, elem, openBox) {
        this.fetchItem(feedurl, itemid, elem, openBox);
    },

    fetchItem: function(feedurl, itemid, elem) {
        this.readbox.init(feedurl, itemid, elem)
        $('#newsContent').on('scroll', function(e) {
            var offset = $(this).children('div').offset().top;
            var header_h = $(this).parent().children('header').outerHeight();
            if (offset < header_h) {
                if (!$('#zffixednews > header').hasClass('flying')) {
                    $('#zffixednews > header').addClass('flying');
                }
            } else {
                $('#zffixednews > header').removeClass('flying');
            }
        });
    },
    
    hideZebrabar: function() {
        var self = this;
        this.zebrabar.removeClass('deployed');
        this.overlay.removeClass('ontop');
        window.setTimeout(function() {
            self.overlay.removeClass('shown');
        }, 0);
    },

    showZebrabar: function() {
        this.zebrabar.addClass('deployed');
        this.overlay.addClass('ontop').addClass('shown');
    }
}

$(document).ready(function() {
    var zst = new Zest();
});