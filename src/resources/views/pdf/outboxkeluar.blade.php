@extends('pdf.layouts.master')

@section('content')
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
                <div class="right-header"><p style="margin-bottom: 0;">{{ $draft->lokasi }}, {{ parseSetLocaleDate($draft->TglReg, 'id', 'd F Y'); }}</p></div>
                <div class="clearfix"></div>
            </div>
            <div>
                <div class="left-header">&nbsp;</div>
                <div class="right-header"><p style="margin-bottom: 0; margin-top: 9.5px;">Kepada </p></div>
                <div class="clearfix"></div>
            </div>
            <div>
                <div class="left-header">
                    <table class="no-padding-table">
                        <tr>
                            <td style="width: 69px">Nomor</td>
                            <td style="width: 18px">:</td>
                            <td>
                                @if ($draft->nosurat != null)
                                    {{ $draft->nosurat }}
                                @else
                                    .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 69px">Sifat</td>
                            <td style="width: 18px">:</td>
                            <td>{!! $draft->classified->SifatName; !!}</td>
                        </tr>
                        <tr>
                            <td style="width: 69px">Lampiran</td>
                            <td style="width: 18px">:</td>
                            <td>{!! $draft->Jumlah . ' ' . $draft->measureUnit->MeasureUnitName; !!}</td>
                        </tr>
                        <tr>
                            <td style="width: 69px">Hal</td>
                            <td style="width: 18px">:</td>
                            <td>{!! $draft->Hal; !!}</td>
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
        <section id="body-content-section">
            {!! $draft->Konten; !!}
        </section>
        @include('pdf.layouts.signature')
        <section id="carboncopy-content-section">
            <table width="100%" style="line-height: 15px;">
                <tr>
                    <td width="10%" valign="top">
                        @php $greeting = 'Yth.'; @endphp
                        @if ($carbonCopy)
                            Tembusan : <br>
                        @endif
                        @forelse ($carbonCopy as $index => $value)
                            @php
                                $index++;
                                $roleNmae  = ucwords(strtolower($value->RoleName));
                                $str = str_replace('Dan', 'dan', $roleNmae);
                                $str = str_replace('Uptd', 'UPTD', $str);
                                $str = str_replace('Dprd', 'DPRD', $str);
                            @endphp

                            @php $endGreeting = ';'; @endphp

                            @if ($index < $carbonCopy->count())
                                @if ($index == ($carbonCopy->count()-1))
                                    @php $endGreeting = '; dan'; @endphp
                                @endif
                            @else
                                @php $endGreeting = ''; @endphp
                            @endif

                            <table border="0" height="20" width="100">
                                <tr style="width:80px" margin-right="50px" height="20">
                                <td style="text-align: justify; width: 10;"valign="top">{!! $index !!}.</td>
                                <td style="text-align: justify; margin-right:30px;"valign="top">{!! $greeting !!}&nbsp;</td>
                                <td style="text-align: justify; width: 545;"valign="top">{!! $str !!} {!! $endGreeting !!}</td>
                                </tr>
                            </table>
                        @empty
                            @if ($carbonCopy)
                                @php
                                    $roleNmae  = ucwords(strtolower($carbonCopy->RoleName));
                                    $str = str_replace('Dan', 'dan', $roleNmae);
                                    $str = str_replace('Uptd', 'UPTD', $str);
                                    $str = str_replace('Dprd', 'DPRD', $str);
                                @endphp
                                <table border="0" height="20" width="100">
                                    <tr style="width:80px" margin-right="50px" height="20">
                                        <td style="text-align: justify; margin-right:30px;"valign="top">{!! $greeting !!}&nbsp;</td>
                                        <td style="text-align: justify; width: 545;"valign="top">{!! $str !!}</td>
                                    </tr>
                                </table>
                            @endif
                        @endforelse
                    </td>
                </tr>
            </table>
        </section>
    </div>
@endsection
