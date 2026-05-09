@extends(\Illuminate\Support\Facades\Auth::check() ? 'layouts.master' : 'layouts.master4')
@section('breadcrumb')
    @include('components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => route("home"),
        ]
    ], 'active' =>  __(@$title)])
@endsection
@section("content")
    <div class="nk-content m-4">
        <div class="nk-block nk-block-middle nk-auth-body wide-xs">
            <div class=" ">
                <div class="card-inner card-inner-lg">
                    <div class="nk-block-head ml-10" >
                        <div class="brand-logo pb-4 text-center mt-10" >
                            <a href="{{ url('/') }}" class="logo-link">
                                <img src="{{ asset('assets/images/smile/icone-3.png') }}"
                                     srcset="{{ asset('assets/images/smile/icone-3.png') }}" alt="logo" style="width: 200px">
                            </a>
                        </div>

                        <div class="nk-block-head-content">
                            <h4 class="nk-block-title text-center">@lang('unauthorized')</h4>

                        </div>
                        <div class="text-center mt-3">
                            <img src="{{ asset('images/decourager.png') }}"
                                 srcset="{{ asset('images/decourager.png') }}" alt="logo">
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>


@endsection
