<div class= "row"> 
    <div class="col-xs-12">
        {if (isset($error)) }
        <div class="paiement_block">
            <p class="alert alert-warning">{$error}</p>
        </div>
        {/if}
        <div id="loadingContainer">
        	<div class="loading"></div>
            <div class="brand">
              <p>iyzico</p>
            </div>
        </div>
        <div id="iyzipay-checkout-form" class="{$form_class}" style="display:none;">
      		{$response nofilter}
        </div>
        <div class="iyziCards" id="iyziCards">
          <img src="{{$cards}}" class="form-class" />
        <p id="termsError">{$contract_text}</p>
        
        </div>
    </div>
</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
{literal}
<style>
.iyziCards {
    width: 100%;
    text-align: center;
    margin-bottom: 30px;
    margin-top: 30px;
}

.iyziCards img {
  width: 500px;
  margin-bottom: 15px;
  text-align: center;
}

.iyziCards p {
  text-align:center;
  font-weight: bold;
}

.loading{width:40px;height:40px;background-color:#4ec8f1;margin:100px auto;-webkit-animation:sk-rotateplane 1.2s infinite ease-in-out;animation:sk-rotateplane 1.2s infinite ease-in-out}@-webkit-keyframes sk-rotateplane{0%{-webkit-transform:perspective(120px)}50%{-webkit-transform:perspective(120px) rotateY(180deg)}100%{-webkit-transform:perspective(120px) rotateY(180deg) rotateX(180deg)}}@keyframes sk-rotateplane{0%{transform:perspective(120px) rotateX(0) rotateY(0);-webkit-transform:perspective(120px) rotateX(0) rotateY(0)}50%{transform:perspective(120px) rotateX(-180.1deg) rotateY(0);-webkit-transform:perspective(120px) rotateX(-180.1deg) rotateY(0)}100%{transform:perspective(120px) rotateX(-180deg) rotateY(-179.9deg);-webkit-transform:perspective(120px) rotateX(-180deg) rotateY(-179.9deg)}}.brand{margin:auto}.brand p{color:#16a2c5;text-align:center;margin-top:-100px}
</style>
<script>

var contractCheck = document.getElementsByClassName("js-terms");

$( document ).ready(function() {

  if(contractCheck.length == 1) {

    		$("input[name='payment-option']").click(function () {
    		    $("button[class='btn btn-primary center-block']").show();

            if ($("input[id='conditions_to_approve[terms-and-conditions]']").is(':checked')) {

                $("#loadingContainer").hide();
                $("#iyzipay-checkout-form").show();
                $('#iyziCards').hide();
           
            } else {

              $('#iyziCards').show();
              $("#loadingContainer").show();
              $("#iyzipay-checkout-form").hide();                 
            
            }

    		});

    		$("input[data-module-name='iyzipay']").click(function () {
              
                $("button[class='btn btn-primary center-block']").hide();

                $("input[id='conditions_to_approve[terms-and-conditions]']").change(function () {

                    if (this.checked) {
            

                         $("#loadingContainer").hide();
                         $("#iyzipay-checkout-form").show();
                         $('#iyziCards').hide();
                     
            
                    } else {

                       $('#iyziCards').show();
                       $("#loadingContainer").show();
                       $("#iyzipay-checkout-form").hide();
                       
                    }
              });
      });        
  } else {

     $("#loadingContainer").hide();
     $("#iyzipay-checkout-form").show();
     $('#iyziCards').hide();
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
