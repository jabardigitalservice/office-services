@extends('pdf.layouts.master')

@section('content')
    <style>
        body {
            font-size: 12px;
        }
    </style>
    @if ($draft->lampiran != null || $draft->lampiran2 != null || $draft->lampiran3 != null || $draft->lampiran4 != null)
        @php $firstPageBreak = 'always'; @endphp
    @else
        @php $firstPageBreak = 'never'; @endphp
    @endif
    <div style="page-break-after: {{ $firstPageBreak }};">
        <section id="header-section">
            <img style="width: 100%" src="{{ config('sikd.base_path_file') . 'kop/' . $header->Header }}">
        </section>
        <section id="header-content-section">
            <div style="margin-top: 49px">
                <div class="left-header">&nbsp;</div>
                <div class="right-header"><p style="margin-bottom: 0;">{{ ($generateQrCode) ? $draft->lokasi . ', ' . parseSetLocaleDate($draft->TglReg, 'id', 'd F Y') : 'Tempat / Tanggal / Bulan / Tahun'; }}</p></div>
                <div class="clearfix"></div>
            </div>
            <div>
                <div class="left-header">&nbsp;</div>
                <div class="right-header"><p style="margin-bottom: 0; margin-top: 9.5px;">Kepada </p></div>
                <div class="clearfix"></div>
            </div>
            <div>
                <div class="left-header">
                    <table class="table-collapse no-padding-table">
                        <tr>
                            <td valign="top" style="width: 69px">Nomor</td>
                            <td valign="top" style="width: 18px">:</td>
                            <td valign="top">
                                @if ($draft->nosurat != null)
                                    {{ $draft->nosurat }}
                                @else
                                    .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="width: 69px">Sifat</td>
                            <td valign="top" style="width: 18px">:</td>
                            <td valign="top">{!! $draft->classified->SifatName; !!}</td>
                        </tr>
                        <tr>
                            <td valign="top" style="width: 69px">Lampiran</td>
                            <td valign="top" style="width: 18px">:</td>
                            <td valign="top">{!! $draft->Jumlah . ' ' . $draft->measureUnit->MeasureUnitName; !!}</td>
                        </tr>
                        <tr>
                            <td valign="top" style="width: 69px">Hal</td>
                            <td valign="top" style="width: 18px">:</td>
                            <td valign="top" style="width: 220px;" class="align-justify">{!! $draft->Hal; !!}</td>
                        </tr>
                    </table>
                </div>
                <div class="right-header">
                    <div class="right-header__sub-left">
                        <p style="margin-top: 0; position: relative; top: -1px">Yth.</p>
                    </div>
                    <div class="right-header__sub-right" style="line-height: 13px">
                        <p style="margin: 0;">{!! $draft->RoleId_To; !!}</p>
                        <p style="margin: 0; position: relative; left: -3px">di</p>
                        <p style="margin: 0; position: relative; left: 10px">{!! $draft->Alamat; !!}</p>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="clearfix"></div>
            </div>
        </section>
        <section id="body-content-section" style="margin: 0px 0px 10px 69px;">
            {!! $draft->Konten; !!}
        </section>
        @include('pdf.layouts.signature')
        <section id="carboncopy-content-section">
            <table width="100%" style="line-height: 15px;">
                <tr>
                    <td width="100%" valign="top">
                        @if ($customData['carbonCopy'])
                            Tembusan : <br>
                            @php $totalCarbonCopy = count($customData['carbonCopy']); $index = 0; @endphp
                            @foreach ($customData['carbonCopy'] as $value)
                                @php
                                    $index++;
                                    $role = Str::title(strtolower($value));
                                    $str = str_replace('Dan', 'dan', $role);
                                    $str = str_replace('Uptd', 'UPTD', $str);
                                    $str = str_replace('Dprd', 'DPRD', $str);
                                @endphp

                                @php $endGreeting = ';'; @endphp

                                @if ($index < $customData['carbonCopy']->count())
                                    @if ($index == ($customData['carbonCopy']->count()-1))
                                        @php $endGreeting = '; dan'; @endphp
                                    @endif
                                @else
                                    @php $endGreeting = '.'; @endphp
                                @endif

                                <table border="0" width="100%">
                                    <tr margin-right="50px">
                                        @if ($totalCarbonCopy > 1)
                                            <td valign="top" style="text-align: justify; width: 16px;"valign="top">{{ $index }}.</td>
                                        @endif
                                        <td style="text-align: left;" valign="top">Yth. {{ rtrim($str) }}{{ $endGreeting }}</td>
                                    </tr>
                                </table>
                            @endforeach
                        @endif
                    </td>
                </tr>
            </table>
        </section>
    </div>
    @include('pdf.layouts.attachment.attachment')
@endsection
