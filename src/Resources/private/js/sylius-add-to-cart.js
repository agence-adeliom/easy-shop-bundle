/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import $ from 'jquery';

$.fn.extend({
  addToCart() {
    const element = this;
    const url = $(element).attr('action');
    const redirectUrl = $(element).data('redirect');
    const validationElement = $('[sylius-cart-validation-error]');

    element.on('submit', function (e){
        e.preventDefault();
        $.ajax({
            url,
            type: "POST",
            data: element.serialize(),
            dataType: 'json',
            cache: false,
            success: function(response){
                validationElement.addClass('hidden');
                window.location.href = redirectUrl;
            },
            error: function (response){
                validationElement.removeClass('hidden');
                let validationMessage = '';

                Object.entries(response.errors.errors).forEach(([, message]) => {
                    validationMessage += message;
                });
                validationElement.html(validationMessage);
                $(element).removeClass('loading');
            }
        });
    })
  },
});
