@extends('front.front')
@section('content')
    <section class="contact-us position-relative w-100 pt-5 pb-5 white-bg" id="ContactUs">
        <div class="container mt-lg-5 mb-lg-5">
            <div class="row">
                <div class="col-lg-6 col-12">
                    <div class="main-content w-100">
                        <h2 class="h1 fontReadexProBold second-text overflow-hidden">
                            <span class="d-block" data-aos="fade-up">We are sorry to see you go</span>
                        </h2>
                        <p class="font-size-30 font-text w-100 d-block mt-4 overflow-hidden">
                            <span class="d-block" data-aos="fade-down">Please let's know what is the reason</span>
                        </p>
                        <ul class="font-size-18 mt-3" data-aos="fade-up">
                            <li>Enter you phone number and we will contact you soon to delete your account</li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-6 col-12">
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                            {{session('success')}}
                            @php(session()->forget('success'))
                        </div>
                    @endif

                    @if(session()->has('error'))
                        <div class="alert alert-danger">
                            {{session('error')}}
                            @php(session()->forget('error'))
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{$error}}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="contact-form" method="post" action="{{route('front.deleteAccount')}}"
                          class="w-100 row m-0 p-xxl-5 p-4 p-3 grey-bg radius-15">
                        @csrf
                        <div class="col-lg-3 col-3 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.country')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <select name="country_prefix" class="form-control">
                                <option selected value="sa">+966</option>
                            </select>
                        </div>
                        <div class="col-lg-9 col-9 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('app.phone')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <input name="phone" class="form-control" type="tel" required value="{{old('phone')}}">
                        </div>
                        <div class="col-lg-12 col-12 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.verification_code')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <input name="verification_code" class="form-control" type="text" value="{{old('verification_code')}}">
                        </div>
                        <div class="col-lg-12 col-12 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.reason')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <textarea
                                name="reason"
                                class="form-control"
                            ></textarea>
                        </div>
                        <div class="col-lg-6 col-12 form-group mini mt-3">
                            <button class="w-100 btn btn-second me-2" type="submit"
                                    formaction="{{route('front.deleteAccount.sendCode')}}">
                                <span class="d-inline-block f-normal">{{__('front.send_code')}}</span>
                            </button>
                        </div>
                        <div class="col-lg-6 col-12 form-group mini mt-3">
                            <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                            <button data-action='submit' class="w-100 btn btn-second me-2" type="submit" data-aos="fade-down">
                                <span class="d-inline-block f-normal">{{__('front.send')}}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

@endsection
