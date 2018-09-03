$(document).ready(function() {
    $("#module_form").submit(function(a) {
        var e, i, r;
        if (e = $("#iyzipay_api_type").val(), i = $("#iyzipay_api_key").val(), r = $("#iyzipay_api_secret_key").val(), "" != i && "" != r || alert("Api Key ve Secret Key Boş Bırakılamaz !"), "https://sandbox-api.iyzipay.com" == e) {
            if ("sandbox-" == i.substring(0, 8) && "sandbox-" == r.substring(0, 8)) return;
            alert("Sandbox / Test API için Live API Anahtarları kullanılamaz !")
        } else if ("https://api.iyzipay.com" == e) {
            if ("sandbox-" != i.substring(0, 8) && "sandbox-" != r.substring(0, 8)) return;
            alert("Live API için Sandbox / Test API Anahtarları kullanılamaz !")
        }
        a.preventDefault()
    })
});