/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import './shim/shim-jquery';
import './shim/shim-lightbox';
import 'lightbox2/dist/css/lightbox.min.css';

import Rater from 'rater-js';

import 'slick-carousel';
import 'slick-carousel/slick/slick.css';

import './sylius-toggle';
import './sylius-api-login';
import './sylius-api-toggle';

import './sylius-add-to-cart';
import './sylius-address-book';
import './sylius-province-field';
import './sylius-variant-images';
import './sylius-variants-prices';

$(document).ready(() => {

  $('form.loadable button[type=submit]').on('click', (event) => {
    $(event.currentTarget).closest('form').addClass('loading');
  });

  $('.alert .close').on('click', (event) => {
    $(event.currentTarget).closest('.message').fadeOut();
  });

  $('[data-toggles]').toggleElement();

  $('#sylius_checkout_address_customer_email').apiEmailCheck({
    dataType: 'json',
    method: 'GET',
  }, $('#sylius-api-login-form'));

  $('#sylius-api-login').apiLogin({
    method: 'POST'
  });

  $('#sylius-product-adding-to-cart').addToCart();

  $('#sylius-shipping-address').addressBook();
  $('#sylius-billing-address').addressBook();

  $(document).provinceField();
  $(document).variantPrices();
  $(document).variantImages();

  $('body').find('input[autocomplete="off"]').prop('autocomplete', 'disable');

  // .rating({
  //   fireOnInit: true,
  //   onRate(value) {
  //     $('[name="sylius_product_review[rating]"]:checked').removeAttr('checked');
  //     $(`#sylius_product_review_rating_${value - 1}`).attr('checked', 'checked');
  //   },
  // });

  $('.star.rating').each(function (index, elm) {
    console.log(elm);

    elm.rater = Rater({
      element: elm,
      max: $(elm).data("maxRating"),
      step: 1,
      starSize: 18,
      rateCallback: (rating, done) => {
        $('[name="sylius_product_review[rating]"]:checked').removeAttr('checked');
        $(`#sylius_product_review_rating_${rating - 1}`).attr('checked', 'checked');
        elm.rater.setRating(rating);
        done();
      }
    })

    if($(elm).data("averageRating")){
      elm.rater.setRating($(elm).data("averageRating"));
    }else{
      elm.rater.setRating($(elm).data("rating"));
    }
  })

  $('.carousel').slick({
    infinite: true,
    slidesToShow: 2,
    slidesToScroll: 1,
    prevArrow: $('.carousel-left'),
    nextArrow: $('.carousel-right'),
    appendArrows: false
  });
});
