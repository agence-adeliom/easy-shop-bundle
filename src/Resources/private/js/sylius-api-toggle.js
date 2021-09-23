/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//import 'semantic-ui-css/components/api';
import $ from 'jquery';

$.fn.extend({
  apiEmailCheck({
    method,
    dataType = 'json',
  }, toggleableElement, isHidden = true) {
    const element = this;

    if (isHidden) {
      toggleableElement.hide();
    }

    const check = () => {
      const email = $('#sylius_checkout_address_customer_email').val();
      if (email.length < 3) {
        toggleableElement.hide();
        return false;
      }

      $.ajax({
        url: element.attr('data-url'),
        type: method,
        dataType: dataType,
        data: {
          email: email
        },
        success: function (response) {
          if($('#sylius_checkout_address_customer_email').val() === response.username){
            toggleableElement.show();
          }else{
            toggleableElement.hide();
          }
        },
        error: function (xhr) {
          toggleableElement.hide();
        },
      })
    }
    if ($('#sylius_checkout_address_customer_email').val()){
      check();
    }

    element.on("change", function (e){
      check();
    })
  },
});
