{*
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
*  @author    paywithiyzico <info@iyzico.com>
*  @copyright 2018 paywithiyzico
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of paywithiyzico
*}

<div class= "row">
    <div class="col-xs-12">
        {if (isset($error)) }
            <div class="paiement_block">
                <p class="alert alert-warning">{$error}</p>
            </div>
        {/if}
        <div>
            <p>{$pwi_description_first}<br>
                {$pwi_description_second}</p>
        </div>
        <div id="pwiLoadingContainer">
            <div class="pwiLoading"></div>
            <div class="pwiBrand">
                <p>iyzico</p>
            </div>
        </div>
        <div id="paywithiyzico-checkout-form" class="{$form_class}" style="display:none;">
            {$pwi nofilter}
            <script type="text/javascript">
                var pwi;
                pwi = '{$pwi nofilter}';
                console.log(pwi);
            </script>
        </div>
        <div class="pwiCards" id="pwiCards">
            <p id="termsError">{$contract_text}</p>
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
{literal}
    <style>
        .pwiCards {
            width: 100%;
            text-align: center;
            margin-bottom: 30px;
            margin-top: 30px;
        }

        .pwiCards img {
            width: 500px;
            margin-bottom: 15px;
            text-align: center;
        }

        img[src*="paywithiyzico"] {
            width: 20%;
        }

        .pwiCards p {
            text-align:center;
            font-weight: bold;
        }

        #checkout-payment-step label {
            text-align: left;
        }

        .pwiLoading{width:40px;height:40px;background-color:#1E64FF;margin:100px auto;-webkit-animation:sk-rotateplane 1.2s infinite ease-in-out;animation:sk-rotateplane 1.2s infinite ease-in-out}@-webkit-keyframes sk-rotateplane{0%{-webkit-transform:perspective(120px)}50%{-webkit-transform:perspective(120px) rotateY(180deg)}100%{-webkit-transform:perspective(120px) rotateY(180deg) rotateX(180deg)}}@keyframes sk-rotateplane{0%{transform:perspective(120px) rotateX(0) rotateY(0);-webkit-transform:perspective(120px) rotateX(0) rotateY(0)}50%{transform:perspective(120px) rotateX(-180.1deg) rotateY(0);-webkit-transform:perspective(120px) rotateX(-180.1deg) rotateY(0)}100%{transform:perspective(120px) rotateX(-180deg) rotateY(-179.9deg);-webkit-transform:perspective(120px) rotateX(-180deg) rotateY(-179.9deg)}}.pwiBrand{margin:auto}.pwiBrand p{color:#1E64FF;text-align:center;margin-top:-100px}
    </style>
    <script>

        var contractCheck = document.getElementsByClassName("js-terms");

        $( document ).ready(function() {

            if(contractCheck.length == 1) {

                $("input[name='payment-option']").click(function () {
                    $("button[class='btn btn-primary center-block']").show();

                    if ($("input[id='conditions_to_approve[terms-and-conditions]']").is(':checked')) {

                        $("#pwiLoadingContainer").hide();
                        $("#paywithiyzico-checkout-form").show();
                        $('#pwiCards').hide();

                    } else {

                        $('#pwiCards').show();
                        $("#pwiLoadingContainer").show();
                        $("#paywithiyzico-checkout-form").hide();

                    }

                });

                $("input[data-module-name='paywithiyzico']").click(function () {

                    $("button[class='btn btn-primary center-block']").hide();

                    $("input[id='conditions_to_approve[terms-and-conditions]']").change(function () {

                        if (this.checked) {


                            $("#pwiLoadingContainer").hide();
                            $("#paywithiyzico-checkout-form").show();
                            $('#pwiCards').hide();


                        } else {

                            $('#pwiCards').show();
                            $("#pwiLoadingContainer").show();
                            $("#paywithiyzico-checkout-form").hide();

                        }
                    });
                });
            } else {

                $("input[name='payment-option']").click(function () {
                    $("button[class='btn btn-primary center-block']").show();
                });

                $("input[data-module-name='paywithiyzico']").click(function () {

                    $("button[class='btn btn-primary center-block']").hide();

                    $("#pwiLoadingContainer").hide();
                    $('#pwiCards').hide();
                    $("#paywithiyzico-checkout-form").show();

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
{/literal}
