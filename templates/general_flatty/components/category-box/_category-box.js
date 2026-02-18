
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.10.1
 *  LICENSE: FL1KU6QLIUJA - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: bizlisting.xyz
 *  FILE: _CATEGORY-BOX.JS
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2026 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

(function(){
    flUtil.loadStyle(rlConfig['tpl_base'] + 'components/popover/popover.css');
    flUtil.loadScript(rlConfig['tpl_base'] + 'components/popover/_popover.js', function(){
        $('.categories-box__more-subcategories').each(function(){
            var $container = $('<div>').css({'overflow-x': 'hidden'});
            var $categories = $(this).closest('.categories-box__subcategories').find('.categories-box__hidden');
            var categoriesCount = $categories.length;
            if ($categories.length > 24) {
                $categories.addClass('col-sm-6 col-md-3');
                $categories = $('<div class="row">').append($categories);
            }
            $container.append($categories);

            (function(count, $link){
                $link.popover({
                    content: $container,
                    fixed: true,
                    setPositionTimeout: 1,
                    onShow: function($body){
                        $body.find('.body:first').addClass('overflow-auto h-100');
                        $body.find('.categories-box__hidden').removeClass('d-none');
                    },
                    onClick: function(popover){
                        // Reassign popover size if there are more than 42 categories
                        if (count > 24 && popover.options.width == 'auto') {
                            popover.options.width = 800;
                        }
                        popover.click();
                    }
                });
            })(categoriesCount, $(this));
        });
    });

    $('.categories-box__plus').click(function(){
        var $use = $(this).find('use');

        $(this).closest('.categories-box__item').toggleClass('categories-box__item_opened');
        $use.attr('xlink:href', '#core-icon-' + ($use.attr('xlink:href') == '#core-icon-plus' ? 'minus' : 'plus'));
    });
}());
