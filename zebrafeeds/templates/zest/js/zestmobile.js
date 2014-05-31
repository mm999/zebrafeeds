function Zest() {
    this.wrapper;
    this.views;
    this.header;
    this.footer;
    
    this.spinner;
    this.init()
    this.animationDuration = 0.2; // seconds
}

Zest.prototype = {
    init: function() {
        this.wrapper = $('#wrapper');
        this.wrapper.children('section').first().addClass('reading');
        this.bindAnchors();
    },
    
    bindAnchors: function() {
        var self = this;
        
        // internal
        $(this.wrapper).on('click', 'a[href^="#"]', function(e) {
            // var transition = $(this).data('transition');
            e.preventDefault();
            var reverse = $(this).data('direction') ? true : false;
            
            var target = this.href.substring(this.href.lastIndexOf('#'), this.href.length);
            if (target != '#') {
                var source = $(this).data('href');
                self.changeView(target, source, false, reverse);
            }
        });
        $(this.wrapper).on('click', 'a[data-role="link"]', function(e) {
            // var transition = $(this).data('transition');
            e.preventDefault();
            var reverse = $(this).data('direction') ? true : false;
            var source = $(this).attr('href');
            self.changeView(null, source, false, reverse);
        });
    },
    
    loadView: function(source, complete) {
        // trigger event onViewBeforeLoad?
        var self = this;
        // load view
        $.ajax({
            url: source,
            // dataType: 'html'
        }).done(function(rsp) {
            complete.call(self, rsp);
        });
        // trigger event onViewBeforeShow
        
        // show view
        
        // trigger onViewShow
        
    },
    
    changeView: function(view, source, force_load, reverse) {
        // trigger event onViewBeforeChange
        var self = this;
        if (source) {
            this.loadView(source, function(rsp) {
                console.log(rsp);
                console.log($('body', rsp));
            }); //or showView(view)
        } else {
            self.showView(view, reverse);
        }
    },
    
    showView: function(view, reverse) {
        view = $(view, this.wrapper);
        var evt = new CustomEvent('beforeShow');
        
        view.get(0).dispatchEvent(evt);
        if (!reverse) {
            $('.reading', this.wrapper).removeClass('reading').addClass('slided-left');
            $(view, this.wrapper).addClass('reading');
        } else {
            $('.reading', this.wrapper).removeClass('reading');
            $(view, this.wrapper).removeClass('slided-left').addClass('reading');
        }
        
        evt = new CustomEvent('show');
        view.get(0).dispatchEvent(evt);
    },
    
    hideView: function() {},
    
    showSpinner: function() {},
    
    hideSpinner: function() {},
    
    
}
var zest = new Zest();

/*document.getElementById('about').addEventListener('beforeShow', function(e) {
    console.log('beforeShow', this)
});
*/
/*document.getElementById('about').addEventListener('show', function(e) {
    console.log('show');
    var self = this;
    $.ajax({
        type: "GET",
        url: 'http://zebrafeeds.dev/newsfeeds/zebrafeeds.php?type=jsonitem&xmlurl=http%3A//rss.slashdot.org/Slashdot/slashdot&itemid=b6fb0d06112a2185ffab28f06a1985ef',
        success: function(rsp) {
            console.log(rsp);
            // $('.content', self).html(rsp);
        }
    });
});*/
