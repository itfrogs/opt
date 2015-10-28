/**
 * Created by snark on 9/8/15.
 */

(function ($) {
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
                    var sku_id = $(this).data('id');
                    var append = '';
                    for (var key in self.categories) {
                        var value = '';
                        var id = 0;
                        if (self.skus[sku_id].categories[self.categories[key].id]) {
                            value = self.skus[sku_id].categories[self.categories[key].id].price;
                            id = self.skus[sku_id].categories[self.categories[key].id].price_id;
                        }
                        console.log(self.categories[key].id);
//                        console.log(value);
                        append += '<br />' +
                        ' <label>' + self.categories[key].name + '<br />' +
                        ' <input type="text" value="'+value+'" ' +
                        ' data-id="'+ id +'" ' +
                        ' data-usercategory="'+ self.categories[key].id +'" ' +
                        ' data-product_id="'+self.product['id']+'" ' +
                        ' data-sku_id="'+sku_id+'" ' +
                        ' onchange="$.Opt.priceChange(this)" ' +
                        ' />' +
                        '</label>' +
                        '';
                    }
                    $(this).find('.s-price').append(append);
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

