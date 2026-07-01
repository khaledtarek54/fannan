<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hayperpay payment</title>
</head>
<style>
    .wpwl-apple-pay-button {
        -webkit-appearance: -apple-pay-button !important;
    }
</style>
<body>
<script src="{{ $paymentUrl }}" 
        crossorigin="anonymous"></script>
<form class="paymentWidgets" data-brands="APPLEPAY VISA MASTER MASTERDEBIT AMEX MADA"></form>
{{--<form class="paymentWidgets" data-brands="ALIA ALIADEBIT AMEX APPLEPAY ARGENCARD CABAL CABALDEBIT CARNET CARTEBANCAIRE CARTEBLEUE CENCOSUD CLICK_TO_PAY DANKORT DINERS  PAYPAY PAYSAFECARD PAYTRAIL_VA PF_KARTE_DIRECT PICPAY PIX POSTPAY PRZELEWY RATENKAUF ROCKETFUEL SANTANDER_FINANCING SANTANDER_INVOICE SIBS_MULTIBANCO SOFINCOSANSFRAIS SWISSBILLING TRUSTLY TWINT VIPPS WECHAT_PAY YANDEX_CHECKOUT ZINIA_BNPL ZINIA_INSTALLMENTS ZINIA_SLICE_IN3"></form>--}}
<script>
    var wpwlOptions = {
        applePay: {
            displayName: "MyStore",
            total: {label: "COMPANY, INC."},
            supportedNetworks: ["applepay", "mada", "masterCard", "visa"]
        }
    }
</script>
</body>
</html>
