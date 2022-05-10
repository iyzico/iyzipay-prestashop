/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    iyzico <info@iyzico.com>
*  @copyright 2018 iyzico
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of iyzico
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

<script>

    var contractCheck = document.getElementsByClassName("js-terms");

    $( document ).ready(function() {

        if(contractCheck.length == 1) {

            $("input[name='payment-option']").click(function () {
                $("button[class='btn btn-primary center-block']").show();

                if ($("input[id='conditions_to_approve[terms-and-conditions]']").is(':checked')) {

                    $("#iyzicoLoadingContainer").hide();
                    $("#iyzipay-checkout-form").show();
                    $('#iyziCards').hide();

                } else {

                    $('#iyziCards').show();
                    $("#iyzicoLoadingContainer").show();
                    $("#iyzipay-checkout-form").hide();

                }

            });

            $("input[data-module-name='iyzipay']").click(function () {

                $("button[class='btn btn-primary center-block']").hide();

                $("input[id='conditions_to_approve[terms-and-conditions]']").change(function () {

                    if (this.checked) {


                        $("#iyzicoLoadingContainer").hide();
                        $("#iyzipay-checkout-form").show();
                        $('#iyziCards').hide();


                    } else {

                        $('#iyziCards').show();
                        $("#iyzicoLoadingContainer").show();
                        $("#iyzipay-checkout-form").hide();

                    }
                });
            });
        } else {

            $("input[name='payment-option']").click(function () {
                $("button[class='btn btn-primary center-block']").show();
            });

            $("input[data-module-name='iyzipay']").click(function () {

                $("button[class='btn btn-primary center-block']").hide();

                $("#iyzicoLoadingContainer").hide();
                $('#iyziCards').hide();
                $("#iyzipay-checkout-form").show();

            });
        }

        $(".material-icons").click(function(){

            location.reload(true);

        });

        $("#promo-code > form ").submit(function(){

            var promoStatus = document.getElementsByClassName("promo-input");
            var promoValue = promoStatus[0].value.length;

            if(promoValue != 0) {
                location.reload(true);
            }
        });
    });
</script>
