@extends('front.front')
@section('content')
    <section class="terms-header position-relative w-100 " style="background-color: #fce5ac" id="termsHeader">
        <div class="container">
            <div class="row pt-3 pb-3">
                <div class="col-lg-8 offset-lg-2 col-12">
                    <div class="main-content w-100">
                        <h1 class="h1 fontReadexProBold second-text overflow-hidden">
                            <span class="d-block" data-aos="fade-up"> {{__('front.terms')}}</span>
                        </h1>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="terms-details position-relative grey-bg pt-5 pb-5 font-text" id="termsDetails">
        <div class="container">
            <div class="row align-items-lg-center">
                <div class="col-lg-8 offset-lg-2 col-12">
                    {!! $terms !!}
                </div>
            </div>
        </div>
    </section>
@endsection
