/**
 * Created by snark on 9/8/15.
 */

(function ($) {

    $(document).ajaxSuccess(function(e,xhr,settings){
        if (xhr.responseJSON && xhr.responseJSON.data && parseInt(xhr.responseJSON.data.id) > 0) {
            $.post('?plugin=opt&action=getprice', {
                data: {product_id: $('#shop-productprofile').data('product-id')}
            }, function (response) {
                $.Opt.categories = response.data.categories;
                $.Opt.skus = response.data.skus;
                $.Opt.init();
            });
        }
    });

    $('.add-sku').on('click', function() {
        setTimeout(function() {
            $.Opt.init();
        }, 50);
    });

    $.Opt = {
        categories: null,
        product: null,
        skus: null,
        localization: null,

        init: function () {
            var self = this;
            try {
                var table = $('#s-product-edit-forms .s-product-form.main table.s-product-skus:first');
                var skus = table.find('tbody:first');
                skus.find('tr').each(function() {
                    var opt_input = $(this).find('.optInput');
                    if (!opt_input[0]) {
                        var sku_id = parseInt($(this).data('id'));
                        var append = '';
                        for (var key in self.categories) {
                            var value = '';
                            var id = 0;
                            if (sku_id > 0 && self.skus[sku_id] && self.skus[sku_id].categories[self.categories[key].id]) {
                                value = self.skus[sku_id].categories[self.categories[key].id].price;
                                id = self.skus[sku_id].categories[self.categories[key].id].price_id;
                            }
                            append += '<br />' +
                                ' <label>' + self.categories[key].name + '<br />' +
                                ' <input class="optInput" type="text" value="'+value+'" name="skus['+sku_id+'][optprice]['+self.categories[key].id+'][price]"' +
                                ' data-id="'+ id +'" ' +
                                ' data-usercategory="'+ self.categories[key].id +'" ' +
                                ' data-product_id="'+self.product['id']+'" ' +
                                ' data-sku_id="'+sku_id+'" ' +
//                                ' onchange="$.Opt.priceChange(this)" ' +
                                ' />' +
                                '</label>' +
                                ' <input class="optInput" type="hidden" value="'+id+'" name="skus['+sku_id+'][optprice]['+self.categories[key].id+'][id]" />' +
                                '';
                        }
                        $(this).find('.s-price').append(append);
                    }
                });
            } catch (e) {
                $.shop.error(e.message, e);
            }
            return false;
        },
        priceChange: function(e) {
            var data = {
                id: $(e).data('id'),
                product_id: $(e).data('product_id'),
                sku_id: $(e).data('sku_id'),
                user_category_id: $(e).data('usercategory'),
                price: $(e).val()
            };

            $.post('?plugin=opt&action=saveprice', {
                data: data
            }, function (response) {

            });

        }
    }
})(jQuery);


