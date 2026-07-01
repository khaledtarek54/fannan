@extends('front.front')
@section('content')
    <section class="contact-us position-relative w-100 pt-5 pb-5 white-bg" id="ContactUs">
        <div class="container mt-lg-5 mb-lg-5">
            <div class="row">
                <div class="col-lg-6 col-12">
                    <div class="main-content w-100">
                        <p class="font-size-30 font-text w-100 d-block mt-4 overflow-hidden">
                            <span class="d-block" data-aos="fade-down">{{__('front.glade_to_serve_you')}}</span>
                        </p>
                        <ul class="font-size-18 mt-3" data-aos="fade-up">
                            <li>{{__('front.fill_information')}}</li>
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

                    <form id="contact-form" method="post" action="{{route('front.contact.store')}}"
                          class="w-100 row m-0 p-xxl-5 p-4 p-3 grey-bg radius-15">
                        @csrf
                        <div class="col-lg-9 col-9 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.name')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <input name="name" class="form-control" type="text" required value="{{old('name')}}">
                        </div>
                        <div class="col-lg-9 col-9 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.email')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <input name="email" class="form-control" type="text" required value="{{old('email')}}">
                        </div>
                        <div class="col-lg-9 col-9 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.phone')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <input name="phone" class="form-control" type="tel" required value="{{old('phone')}}">
                        </div>
                        <div class="col-lg-12 col-12 form-group mini mb-3">
                            <label class="d-flex mb-2">
                                <span class="me-2">{{__('front.details')}}</span>
                                <span class="red-text">* </span>
                            </label>
                            <textarea
                                name="message"
                                class="form-control"
                            ></textarea>
                        </div>
                        <div class="col-lg-4 col-12 form-group mini mt-3">
                            <button data-action='submit' class="w-100 btn btn-second me-2" type="submit" data-aos="fade-down">
                                <span class="d-inline-block ">{{__('front.send')}}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

@endsection
