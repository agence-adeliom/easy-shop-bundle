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
  apiLogin({
    method,
    dataType = 'json'
  }) {
    const element = this;
    const passwordField = element.find('input[type="password"]');
    const emailField = element.find('input[type="email"]');
    const csrfTokenField = element.find('input[type="hidden"]');
    const signInButton = element.find('#sylius-api-login-submit');
    const validationField = element.find('#sylius-api-validation-error');

    signInButton.on('click', function (e){
      e.preventDefault();

      $.ajax({
        url: signInButton.attr('data-url'),
        type: method,
        dataType: dataType,
        data: {
          _username: emailField.val(),
          _password: passwordField.val(),
          [csrfTokenField.attr('name')]: csrfTokenField.val(),
        },
        success: function (response) {
          if(response.success){
            element.remove();
            window.location.reload();
          }else{
            validationField.removeClass('hidden');
            validationField.html(response.message);
          }
        },
        error: function (xhr) {
          const response = xhr.responseJSON;
          validationField.removeClass('hidden');
          validationField.html(response.message);
        },
      });
    })
  },
});
