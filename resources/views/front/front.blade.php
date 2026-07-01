<!DOCTYPE html>
<html lang="{{app()->getLocale()}}" dir="{{app()->getLocale() == 'ar' ? 'rtl' : 'ltr'}}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


{{--    <link rel="manifest" href="{{asset('front/favicon/site.webmanifest')}}">--}}
    @if(app()->getLocale() == 'ar')
        <link rel="stylesheet" href="{{asset('front/dist/css/bootstrap.rtl.min.css')}}">
    @else
        <link rel="stylesheet" href="{{asset('front/dist/css/bootstrap.min.css')}}">
    @endif
    <link rel="stylesheet" href="{{asset('front/dist/css/main.min.css')}}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>
    <title>{{__('app.fannan')}}</title>
    @yield('style')
</head>
<body>
{{--<div class="loader"><img src="{{asset('front/dist/img/loading.gif')}}" alt="{{__('app.name')}}"></div>--}}
<div class="topBar position-relative w-100">
    <div class="container">
        <nav class="navbar navbar-expand-lg d-flex w-100">
            <a class="navbar-brand order-lg-1 order-1" href="{{route('front.terms')}}">
                <img src="{{asset('images/logo-gold.png')}}" alt="{{__('app.fannan')}}">
            </a>
            <button class="navbar-toggler order-lg-2 order-3 ms-3" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse order-lg-3 order-4" id="navbarSupportedContent">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('front.privacy')}}">
                            {{__('front.privacy')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('front.terms')}}">
                            {{__('front.terms')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('front.about')}}">
                            {{__('front.about')}}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('front.contact')}}">
                            {{__('front.contact')}}
                        </a>
                    </li>
                </ul>
            </div>
{{--            <div class="d-inline-flex ms-lg-auto order-lg-4 order-2" id="mainBtn">--}}
{{--                <div class="nav-item d-lg-inline-block d-none">--}}
{{--                    @if(\App\Helpers\IsRTL::run())--}}
{{--                        <a class="nav-link"--}}
{{--                           href="{{ LaravelLocalization::getLocalizedURL("en", null, [], true) }}">En</a>--}}
{{--                    @else--}}
{{--                        <a class="nav-link"--}}
{{--                           href="{{ LaravelLocalization::getLocalizedURL("ar", null, [], true) }}">ع</a>--}}
{{--                    @endif--}}
{{--                </div>--}}
{{--                <a class="btn btn-outline-second" href="{{route('login')}}">--}}
{{--                    <span class="d-inline-block">{{__('front.enter')}} {{__('front.your_account')}}</span>--}}
{{--                                        <span class="d-lg-inline-block d-none ms-2"> </span>--}}
{{--                </a>--}}
{{--            </div>--}}
        </nav>
    </div>
</div>

@yield('content')
<footer class="position-relative second-bg pt-5 pb-5 white-text">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-12">
                <a class="foot-logo" href="#">
                    <img src="{{asset('images/logo-white.png')}}" alt="{{__('app.fannan')}}">
                </a>
            </div>
            <div class="col-lg-3 col-12 mt-lg-0 mt-4">
{{--                <h5 class="w-100 font-size-21 main-text p-2 mb-2 text-black">{{__('front.quick_links')}}</h5>--}}

            </div>
            <div class="col-lg-3 col-12 mt-lg-0 mt-4"><h5
                    class="w-100 font-size-21 main-text p-2 mb-2 text-black">{{__('front.quick_links')}}</h5>
                <div class="w-100 foot-links">
                    <a class="d-block white-text main-text-hover font-size-16 p-2"
                       href="{{route('front.privacy')}}"> {{__('front.privacy')}}</a>
                </div>
                <div class="w-100 foot-links">
                    <a class="d-block white-text main-text-hover font-size-16 p-2"
                       href="{{route('front.terms')}}">{{__('front.terms')}} </a>
                </div>
                <div class="w-100 foot-links">
                    <a class="d-block white-text main-text-hover font-size-16 p-2"
                       href="{{route('front.about')}}">{{__('front.about')}}</a>
                </div>
                <div class="w-100 foot-links">
                    <a class="d-block white-text main-text-hover font-size-16 p-2"
                       href="{{route('front.contact')}}">{{__('front.contact')}}</a>
                </div>
            </div>
            <div class="col-lg-3 col-12 mt-lg-0 mt-4" >
                <div class="w-100">
                    <a class="btn w-100 d-flex justify-content-center font-size-18 align-items-center" style="background-color: #fce5ac"
                       href="https://wa.me/{{$whatsapp}}" target="_blank">
                        <i class="fa-brands fa-whatsapp me-2 font-size-22"> </i>
                        <span>{{__('front.contact_whatsapp')}}</span>
                    </a>
                </div>
                <div class="w-100 d-block follow-links d-flex mt-3">
                    <a class="p-2 white-text font-size-24 main-text-hover" href="#">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                    <a class="p-2 white-text font-size-24 main-text-hover" href="#">
                        <i class="fa-brands fa-twitter"></i>
                    </a>
                    <a class="p-2 white-text font-size-24 main-text-hover" href="#">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a class="p-2 white-text font-size-24 main-text-hover" href="#">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                    <a class="p-2 white-text font-size-24 main-text-hover" href="#">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12 mt-5">
                <div class="w-100 copyrights font-size-14">{{__('front.copyright')}}
                    @ {{date('Y')}} {{__('app.fannan')}}</div>
            </div>
        </div>
    </div>
</footer>
<script src="{{asset('front/dist/js/lib.min.js')}}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{asset('front/dist/js/core.min.js')}}"></script>
<script>
    $('.select2').select2({});
</script>

</body>
</html>

