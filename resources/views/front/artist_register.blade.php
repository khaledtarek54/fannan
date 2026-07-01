@extends('front.front')
@section('content')
    <section class="contact-us position-relative w-100 white-bg text-center">
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
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

                <form id="contact-form" method="post" action="{{route('user.register')}}"
                      class="w-100 row m-0 p-xxl-5 p-4 p-3 grey-bg radius-15">
                    @csrf
                    <div class="text-center mb-3">
                        <h3> Register</h3>
                    </div>
                    <input name="role" value="artist" hidden>
                    <div class="col-lg-12 col-12 form-group mini mb-3">
                        <label class="d-flex mb-2">
                            <span class="me-2">{{__('front.name')}}</span>
                            <span class="red-text">* </span>
                        </label>
                        <input name="name" type="text" class="form-control" placeholder="eg: Tamer Hosni" required>
                    </div>
                    <div class="col-lg-3 col-3 form-group mini mb-3">
                        <label class="d-flex mb-2">
                            <span class="me-2">{{__('front.country')}}</span>
                            <span class="red-text">* </span>
                        </label>
                        <select name="phone_prefix" class="form-control">
                            <option selected value="=966">+966</option>
                        </select>
                    </div>
                    <div class="col-lg-9 col-9 form-group mini mb-3">
                        <label class="d-flex mb-2">
                            <span class="me-2">{{__('app.phone')}}</span>
                            <span class="red-text">* </span>
                        </label>
                        <input name="phone" class="form-control" type="tel" placeholder="5xxxxxxx" required value="{{old('phone')}}">
                    </div>
                    <div class="col-lg-12 col-12 form-group mini mb-3">
                        <label class="d-flex mb-2">
                            <span class="me-2">{{__('front.password')}}</span>
                            <span class="red-text">* </span>
                        </label>
                        <input name="password" type="password" class="form-control" required>
                    </div>
                    <div class="col-lg-4 col-12 form-group mini mt-3 justify-content-end">
                        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                        <button data-action='submit' class="w-100 btn btn-second me-2" type="submit"
                                data-aos="fade-down">
                            <span class="d-inline-block f-normal">Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
