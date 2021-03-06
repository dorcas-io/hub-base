@extends('layouts.app')
@section('head_css')
    <link href="{{ cdn('css/layouts/page-center.css') }}" type="text/css" rel="stylesheet">
@endsection
@section('body')
    <div id="error-page">
        <div class="row">
            <div class="col s12">
                <div class="browser-window">
                    <div class="top-bar">
                        <div class="circles">
                            <div id="close-circle" class="circle"></div>
                            <div id="minimize-circle" class="circle"></div>
                            <div id="maximize-circle" class="circle"></div>
                        </div>
                    </div>
                    <div class="content">
                        <div class="row">
                            <div id="site-layout-example-top" class="col s12">
                                <p class="flat-text-logo center white-text caption-uppercase">{{ $alarm['title'] or 'You are not authorized to access this content :(' }}</p>
                            </div>
                            <div id="site-layout-example-right" class="col s12 m12 l12">
                                <div class="row center">
                                    <h1 class="text-long-shadow col s12 mt-3">403</h1>
                                    <p class="center white-text">{{ $alarm['text'] or $exception->getMessage() }}.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
