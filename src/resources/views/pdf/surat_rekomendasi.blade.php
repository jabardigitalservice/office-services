@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 16px;
        }
    </style>
    <div style="page-break-after: auto; page-break-inside: auto !important;">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <div style="margin-top: 84px"> <!-- Custom condition header for gubernur level -->
            <p style="text-align: center; font-size: 16px;">
                REKOMENDASI<br>
                NOMOR : {{ $draft->nosurat }}
            </p>
        </div>
        <div style="margin-top: 18px;">
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Dasar</div>
                <div class="column" style="width: 13px">:</div>
                <div class="column align-justify" style="width: 480px;">{!! $draft->Hal !!}</div>
            </div>
            @php $content = json_decode($draft->Konten); @endphp
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Menimbang</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify" style="position: relative; left: 123px; top: -30px; width: 480px;">
                {!! $content->menimbang !!}
            </div>
            <div class="align-justify">
                <p>{{ $draft->approver->role->RoleName }}, memberikan rekomendasi kepada:</p>
            </div>
            @forelse ($customData['receivers'] as $value)
                <div class="row">
                    <div class="column" style="width: 220px">a. Nama/Objek</div>
                    <div class="column" style="width: 13px">:</div>
                </div>
                <div class="align-justify" style="position: relative; left: 240px; top: -20px; width: 370px;">
                    {!! $value->PeopleName !!}
                </div>
                <div class="row">
                    <div class="column" style="width: 220px">b. Jabatan/Tempat/Identitas</div>
                    <div class="column" style="width: 13px">:</div>
                </div>
                <div class="align-justify" style="position: relative; left: 240px; top: -20px; width: 370px;">
                    {!! $value->PeoplePosition !!}
                </div>
            @empty
                -
            @endforelse
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Untuk</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify" style="position: relative; left: 123px; top: -30px; width: 480px;">
                {!! $content->untuk !!}
            </div>
            <div class="align-justify">
                <p style="margin:0;">Demikian rekomendasi ini dibuat untuk dipergunakan seperlunya.</p>
            </div>
        </div>
    </div>
    <div style="margin: 70px 5px 0px 5px;">
        @include('pdf.layouts.signature')
    </div>
@endsection
