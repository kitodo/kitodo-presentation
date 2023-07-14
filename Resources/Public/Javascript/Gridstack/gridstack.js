$( document ).ready(function() {
    var options = { // put in gridstack options here
        disableOneColumnMode: true, // for jfiddle small window size
        float: false,
        handle: '.drag'
    };
    var grid = GridStack.init(options);

    // DEV #########################
    // var count = 0;
    // var items = [
    //     {x: 0, y: 0, w: 2, h: 2},
    //     {x: 2, y: 0, w: 2},
    //     {x: 3, y: 1, h: 2},
    //     {x: 0, y: 2, w: 2},
    // ];
    //
    // addNewWidget = function () {
    //     var node = items[count] || {
    //         x: Math.round(12 * Math.random()),
    //         y: Math.round(5 * Math.random()),
    //         w: Math.round(1 + 3 * Math.random()),
    //         h: Math.round(1 + 3 * Math.random())
    //     };
    //     node.content = String(count++);
    //     grid.addWidget(node);
    //     return false;
    // };

    // DEV ########################

    if (grid) {
        // resize each map
        grid.on('change', function(evt, items) {
            $('.tx-dlf-map').each(function (index) {
                tx_dlf_viewer[index].map.updateSize()
            });
        });
    }
});
