function ZSTLayout(items) {
    this.items = items;
}

ZSTLayout.prototype = {
    constructor: ZSTLayout,
    getHtml: function() {
        var page_div = $('<div class="page"></div>');
        var k = 1;
        $.each(this.items, function(j, item) {
            console.log('item', item);
            var h = '',
                w = '';
            if (k <= 2) h = 'h-60';
            if (k > 2)  h = 'h-40';
            switch (k) {
                case 1: w = 'w-30 box-b-r'; break;
                case 2: w = 'w-70'; break;
                case 3: w = 'w-40 box-b-r'; break;
                case 4: w = 'w-30 box-b-r'; break;
                case 5: w = 'w-30'; break;
            }
            var title = $('<h3></h3>')
                .addClass('newstitle')
                .append(
                    $('<a></a>').attr(
                        'id', item.id
                    ).attr(
                        'href', item.channel.xmlurl
                    ).html(item.title)
                );
            var content = $('<div></div>')
                            .addClass('newsdesc')
                            .html(item.description);
            $('<div></div>')
                .addClass(h).addClass(w)
                .addClass('item')
                .append(title)
                .append(content)
                .appendTo(page_div);
            k++;
        });
		page_div.css('height', 'calc(100% - 50px)');
        return page_div;
    }
}