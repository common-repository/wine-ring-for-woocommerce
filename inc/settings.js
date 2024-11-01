'use strict';

console.log('settings js file has been added');

(function( $ ) {

    // Add Color Picker to all inputs that have 'color-field' class
    $(function() {
        $('input[id="wr4wc_setting_crop_fill_hex_color"]').wpColorPicker();
    });

})( jQuery );
