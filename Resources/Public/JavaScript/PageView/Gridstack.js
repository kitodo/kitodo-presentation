/*global GridStack */
$( document ).ready(function() {
    var options = { // Put in gridstack options here
        disableOneColumnMode: true, // For jfiddle small window size
        float: false,
        handle: '.gridstack-dragging-handle',
        minW: 2,
        minH: 2
    };
    var grid = GridStack.init(options);

    if (Cookies.get('gsLayout')) {
        // Only extract saved layout for elements that exist
        var loadedGridLayout = JSON.parse(Cookies.get('gsLayout'));
        $(loadedGridLayout).each(function () {
            if ($("[gs-id='" + this.id + "']").length === 1) {
                var element = $("[gs-id='" + this.id + "']")[0];
                grid.update(element, this);
            }
        });
    }

    $('.reset-gridstack-layout').on('click', function (evt) {
        Cookies.set('gsLayout', '');
        location.reload();
        return false;
    });

    if (grid) {
        // Resize each map
        grid.on('change', function(evt, items) {
            $('.tx-dlf-map').each(function (index) {
                tx_dlf_viewer[index].map.updateSize()
            });
            Cookies.set('gsLayout', JSON.stringify(grid.save(false)));
        });
    }
});
