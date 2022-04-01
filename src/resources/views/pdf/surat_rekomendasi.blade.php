@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 16px;
        }
        .list-no-margin ol {
            counter-reset: section;
            margin: 0;
            padding: 0;
        }

        ul {
            margin: 0;
        }

        .list-no-margin ol li {
            counter-increment: section;
            display: block;
            margin: 0 0 0 20px;
            padding: 0 0 0 20px;
            page-break-inside: avoid;
            position: relative;
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-all;
            text-align: justify;
        }

        .list-no-margin ul li {
            vertical-align: top;
            word-wrap: break-word;
            word-break: break-all;
            text-align: justify;
        }

        .list-no-margin  ol li:before {
            content: counters(section, ".") ".";
            display: block;
            position: absolute;
            left: 3px;
        }
    </style>
    <div style="page-break-after: auto; page-break-inside: auto !important;">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <div style="margin-top: 95px"> <!-- Custom condition header for gubernur level -->
            <p style="text-align: center; font-size: 16px;">
                REKOMENDASI<br>
                @if ($draft->nosurat != null)
                    NOMOR : {{ $draft->nosurat }}
                @else
                    NOMOR : .........../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                @endif
            </p>
        </div>
        <div style="margin-top: 18px;">
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Dasar</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify list-no-margin" style="position: relative; left: 123px; top: -30px; width: 480px;">
                {!! $draft->Hal !!}
            </div>
            @php $content = json_decode($draft->Konten); @endphp
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Menimbang</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify list-no-margin" style="position: relative; left: 123px; top: -30px; width: 480px;">
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
                    {!! Str::title($value->PeoplePosition) !!}
                </div>
            @empty
                -
            @endforelse
            <div class="row has-margin-bottom">
                <div class="column" style="width: 110px">Untuk</div>
                <div class="column" style="width: 13px">:</div>
            </div>
            <div class="align-justify list-no-margin" style="position: relative; left: 123px; top: -30px; width: 480px;">
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
