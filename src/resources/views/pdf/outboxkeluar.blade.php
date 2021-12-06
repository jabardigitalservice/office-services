<html>
    <head>
        <style>
            /** Define the margins of your page **/
            body {
                font-family: "Arial, Helvetica, sans-serif";
                font-size: 12px;
            }
            @page {
                margin: 85px 85.5px 80px 123.1px;
            }

            section#header-section {
                position: fixed;
                top: -60px;
                left: 0px;
                right: 0px;
                height: 0px;

                /** Extra personal styles **/
            }

            footer {
                position: fixed;
                bottom: -70px;
                left: 0px;
                right: 0px;
                height: 50px;

                /** Extra personal styles **/
                text-align: center;
                font-size: 9px;
                margin-left: 5px;
                text-align: center;
            }

            .clearfix {
                clear: both;
            }
            .left-header {
                float: left;
                width: 353.5px;
            }

            .right-header {
                float: left;
                width: 229.5px;
            }

            .right-header__sub-left {
                float: left;
            }

            .right-header__sub-right {
                padding-left: 25px;
            }
            .no-padding-table {
                border-collapse: collapse;
            }
            .no-padding-table td {
                padding: 0;
                margin: 0;
            }
            #header-content-section {
                margin-bottom: 55px;
            }
            #body-content-section {
                text-align: justify;
                line-height: 17px;
                margin: 0px 0px 10px 69px;
            }

            #body-content-section table {
                height: auto !important;
            }
            #body-content-section table td {
                line-height: 14px;
                vertical-align: top;
            }

            .header-attachment {
                margin-top: 11px;
                float: right;
                width: 385px;
                font-size: 11px;
                line-height: 15px;
                position: relative;
                right: -10px;
            }
            .content-attachment table {
                border-collapse: collapse;
                width: auto;
            }
            .content-attachment td {
                padding: 0;
                margin: 0;
                line-height: 16px
            }
        </style>
    </head>
    <body>
        <!-- Define header and footer blocks before your content -->
        <footer>
            Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh
            <br>
            Balai Sertifikasi Elektronik (BSrE) Badan Siber dan Sandi Negara
        </footer>

        <!-- Wrap the content of your PDF inside a main tag -->
        <main>
            @if ($draft->lampiran != null || $draft->lampiran2 != null || $draft->lampiran3 != null || $draft->lampiran4 != null)
                @php $firstPageBreak = 'always'; @endphp
            @else
                @php $firstPageBreak = 'never'; @endphp
            @endif
            <div style="page-break-after: {{ $firstPageBreak }};">
                <section id="header-section">
                    <img style="width: 100%" src="{!! config('sikd.base_path_file') . 'kop/' . $header->Header !!}">
                </section>
                <section id="header-content-section">
                    <div style="margin-top: 49px">
                        <div class="left-header">&nbsp;</div>
                        <div class="right-header"><p style="margin-bottom: 0;">Tempat / Tanggal / Bulan / Tahun </p></div>
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
                <section class="signature-content-section">
                    <div style="float:right; width: 300px;">
                        <p style="text-align: center;">
                            @if ($draft->TtdText == 'PLT')
                                Plt. {!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText == 'PLH')
                                Plh. {!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText2 == 'Atas_Nama')
                                a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                    <br>{!! $draft->reviewer->role->RoleName !!},
                            @elseif ($draft->TtdText2 == 'untuk_beliau')
                                a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                    <br>{!! $draft->sender->parentRole->RoleName !!},
                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                            @else
                                {!! $draft->reviewer->role->RoleName !!},
                            @endif
                        </p>

                        <p style="text-align: center;">PEMERIKSA</p>
                        <div style="border: 1px solid #000000; font-size: 10px; margin: 0px 6px 0px 20px;">
                            <table class="no-padding-table">
                                <tr>
                                    <td rowspan="4">
                                        <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" width="48px" height="48px">
                                    </td>
                                    <td>Ditandatangani secara elekronik oleh:</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div style="margin-bottom: 20px">
                                            @if ($draft->TtdText == 'PLT')
                                                Plt. {!! $draft->reviewer->role->RoleName !!},

                                            @elseif ($draft->TtdText == 'PLH')
                                                Plh. {!! $draft->reviewer->role->RoleName !!},
                                                <br>{!! $draft->reviewer->role->RoleName !!},

                                            @elseif ($draft->TtdText2 == 'Atas_Nama')
                                                a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                <br><{!! $draft->reviewer->role->RoleName !!},

                                            @elseif ($draft->TtdText2 == 'untuk_beliau')
                                                a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                <br>{!! $draft->sender->parentRole->RoleName !!},
                                                <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                                            @else
                                                {!! $draft->reviewer->role->RoleName !!},
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?= $draft->Nama_ttd_konsep ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        {!! $draft->reviewer->Pangkat !!}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </section>
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
            @if ($draft->lampiran != null)
                <div style="page-break-after: {{ $draft->lampiran2 != null ? "always" : "never" }};">
                    <div class="header-attachment">
                        <table style="text-align: justify">
                            <tbody>
                                <tr>
                                    <td style="width: 65px; vertical-align: top;">LAMPIRAN</td>
                                    <td style="width: 31px; vertical-align: top;">:</td>
                                    <td style="vertical-align: top;" colspan="3">
                                        @if ($draft->type_naskah == 'XxJyPn38Yh.1')
                                            SURAT
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.2')
                                            SURAT UNDANGAN
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.3')
                                            SURAT PANGGILAN
                                        @endif
                                        @if ($draft->TtdText == 'PLT')
                                            Plt. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText == 'PLH')
                                            Plh. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText2 == 'Atas_Nama')
                                            {!! $draft->approver->role->RoleName !!},
                                        @else
                                            {!! $draft->reviewer->role->RoleName !!},
                                        @endif
                                        <table class="no-padding-table" style="text-align: justify">
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">NOMOR</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">
                                                    @if ($draft->nosurat != null)
                                                        {{ $draft->nosurat }}
                                                    @else
                                                        .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">TANGGAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">Tanggal / Bulan / Tahun</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">PERIHAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;"> <?= $draft->Hal; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                    <div class="content-attachment">
                        <p style="line-height: 15px; text-align: justify;">
                            {!! $draft->lampiran !!}<br>
                       </p>
                    </div>
                    <section class="signature-content-section">
                        <div style="float:right; width: 300px;">
                            <p style="text-align: center;">
                                @if ($draft->TtdText == 'PLT')
                                    Plt. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText == 'PLH')
                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->sender->parentRole->RoleName !!},
                                        <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                                @else
                                    {!! $draft->reviewer->role->RoleName !!},
                                @endif
                            </p>

                            <p style="text-align: center;">PEMERIKSA</p>
                            <div style="border: 1px solid #000000; font-size: 10px; margin: 0px 6px 0px 20px;">
                                <table class="no-padding-table">
                                    <tr>
                                        <td rowspan="4">
                                            <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" width="48px" height="48px">
                                        </td>
                                        <td>Ditandatangani secara elekronik oleh:</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="margin-bottom: 20px">
                                                @if ($draft->TtdText == 'PLT')
                                                    Plt. {!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText == 'PLH')
                                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br><{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->sender->parentRole->RoleName !!},
                                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                                                @else
                                                    {!! $draft->reviewer->role->RoleName !!},
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= $draft->Nama_ttd_konsep ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            {!! $draft->reviewer->Pangkat !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </section>
                </div>
            @endif
            @if ($draft->lampiran2 != null)
                <div style="page-break-after: {{ $draft->lampiran3 != null ? "always" : "never" }};">
                    <div class="header-attachment">
                        <table style="text-align: justify">
                            <tbody>
                                <tr>
                                    <td style="width: 65px; vertical-align: top;">LAMPIRAN II</td>
                                    <td style="width: 31px; vertical-align: top;">:</td>
                                    <td style="vertical-align: top;" colspan="3">
                                        @if ($draft->type_naskah == 'XxJyPn38Yh.1')
                                            SURAT
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.2')
                                            SURAT UNDANGAN
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.3')
                                            SURAT PANGGILAN
                                        @endif
                                        @if ($draft->TtdText == 'PLT')
                                            Plt. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText == 'PLH')
                                            Plh. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText2 == 'Atas_Nama')
                                            {!! $draft->approver->role->RoleName !!},
                                        @else
                                            {!! $draft->reviewer->role->RoleName !!},
                                        @endif
                                        <table class="no-padding-table" style="text-align: justify">
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">NOMOR</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">
                                                    @if ($draft->nosurat != null)
                                                        {{ $draft->nosurat }}
                                                    @else
                                                        .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">TANGGAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">Tanggal / Bulan / Tahun</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">PERIHAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;"> <?= $draft->Hal; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                    <div class="content-attachment">
                        <p style="line-height: 15px; text-align: justify;">
                            {!! $draft->lampiran2 !!}<br>
                       </p>
                    </div>
                    <section class="signature-content-section">
                        <div style="float:right; width: 300px;">
                            <p style="text-align: center;">
                                @if ($draft->TtdText == 'PLT')
                                    Plt. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText == 'PLH')
                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->sender->parentRole->RoleName !!},
                                        <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                                @else
                                    {!! $draft->reviewer->role->RoleName !!},
                                @endif
                            </p>

                            <p style="text-align: center;">PEMERIKSA</p>
                            <div style="border: 1px solid #000000; font-size: 10px; margin: 0px 6px 0px 20px;">
                                <table class="no-padding-table">
                                    <tr>
                                        <td rowspan="4">
                                            <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" width="48px" height="48px">
                                        </td>
                                        <td>Ditandatangani secara elekronik oleh:</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="margin-bottom: 20px">
                                                @if ($draft->TtdText == 'PLT')
                                                    Plt. {!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText == 'PLH')
                                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br><{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->sender->parentRole->RoleName !!},
                                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                                                @else
                                                    {!! $draft->reviewer->role->RoleName !!},
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= $draft->Nama_ttd_konsep ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            {!! $draft->reviewer->Pangkat !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </section>
                </div>
            @endif
            @if ($draft->lampiran3 != null)
                <div style="page-break-after: {{ $draft->lampiran4 != null ? "always" : "never" }};">
                    <div class="header-attachment">
                        <table style="text-align: justify">
                            <tbody>
                                <tr>
                                    <td style="width: 65px; vertical-align: top;">LAMPIRAN III</td>
                                    <td style="width: 31px; vertical-align: top;">:</td>
                                    <td style="vertical-align: top;" colspan="3">
                                        @if ($draft->type_naskah == 'XxJyPn38Yh.1')
                                            SURAT
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.2')
                                            SURAT UNDANGAN
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.3')
                                            SURAT PANGGILAN
                                        @endif
                                        @if ($draft->TtdText == 'PLT')
                                            Plt. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText == 'PLH')
                                            Plh. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText2 == 'Atas_Nama')
                                            {!! $draft->approver->role->RoleName !!},
                                        @else
                                            {!! $draft->reviewer->role->RoleName !!},
                                        @endif
                                        <table class="no-padding-table" style="text-align: justify">
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">NOMOR</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">
                                                    @if ($draft->nosurat != null)
                                                        {{ $draft->nosurat }}
                                                    @else
                                                        .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">TANGGAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">Tanggal / Bulan / Tahun</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">PERIHAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;"> <?= $draft->Hal; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                    <div class="content-attachment">
                        <p style="line-height: 15px; text-align: justify;">
                            {!! $draft->lampiran3 !!}<br>
                       </p>
                    </div>
                    <section class="signature-content-section">
                        <div style="float:right; width: 300px;">
                            <p style="text-align: center;">
                                @if ($draft->TtdText == 'PLT')
                                    Plt. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText == 'PLH')
                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->sender->parentRole->RoleName !!},
                                        <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                                @else
                                    {!! $draft->reviewer->role->RoleName !!},
                                @endif
                            </p>

                            <p style="text-align: center;">PEMERIKSA</p>
                            <div style="border: 1px solid #000000; font-size: 10px; margin: 0px 6px 0px 20px;">
                                <table class="no-padding-table">
                                    <tr>
                                        <td rowspan="4">
                                            <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" width="48px" height="48px">
                                        </td>
                                        <td>Ditandatangani secara elekronik oleh:</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="margin-bottom: 20px">
                                                @if ($draft->TtdText == 'PLT')
                                                    Plt. {!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText == 'PLH')
                                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br><{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->sender->parentRole->RoleName !!},
                                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                                                @else
                                                    {!! $draft->reviewer->role->RoleName !!},
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= $draft->Nama_ttd_konsep ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            {!! $draft->reviewer->Pangkat !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </section>
                </div>
            @endif
            @if ($draft->lampiran4 != null)
                <div style="page-break-after: never">
                    <div class="header-attachment">
                        <table style="text-align: justify">
                            <tbody>
                                <tr>
                                    <td style="width: 65px; vertical-align: top;">LAMPIRAN IV</td>
                                    <td style="width: 31px; vertical-align: top;">:</td>
                                    <td style="vertical-align: top;" colspan="3">
                                        @if ($draft->type_naskah == 'XxJyPn38Yh.1')
                                            SURAT
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.2')
                                            SURAT UNDANGAN
                                        @elseif ($draft->type_naskah == 'XxJyPn38Yh.3')
                                            SURAT PANGGILAN
                                        @endif
                                        @if ($draft->TtdText == 'PLT')
                                            Plt. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText == 'PLH')
                                            Plh. {!! $draft->reviewer->role->RoleName !!},
                                        @elseif ($draft->TtdText2 == 'Atas_Nama')
                                            {!! $draft->approver->role->RoleName !!},
                                        @else
                                            {!! $draft->reviewer->role->RoleName !!},
                                        @endif
                                        <table class="no-padding-table" style="text-align: justify">
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">NOMOR</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">
                                                    @if ($draft->nosurat != null)
                                                        {{ $draft->nosurat }}
                                                    @else
                                                        .../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">TANGGAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;">Tanggal / Bulan / Tahun</td>
                                            </tr>
                                            <tr>
                                                <td style="width: 80px; vertical-align: top;">PERIHAL</td>
                                                <td style="width: 15px; vertical-align: top;">:</td>
                                                <td style="vertical-align: top;"> <?= $draft->Hal; ?></td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                    <div class="content-attachment">
                        <p style="line-height: 15px; text-align: justify;">
                            {!! $draft->lampiran4 !!}<br>
                       </p>
                    </div>
                    <section class="signature-content-section">
                        <div style="float:right; width: 300px;">
                            <p style="text-align: center;">
                                @if ($draft->TtdText == 'PLT')
                                    Plt. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText == 'PLH')
                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->reviewer->role->RoleName !!},
                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                        <br>{!! $draft->sender->parentRole->RoleName !!},
                                        <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},
                                @else
                                    {!! $draft->reviewer->role->RoleName !!},
                                @endif
                            </p>

                            <p style="text-align: center;">PEMERIKSA</p>
                            <div style="border: 1px solid #000000; font-size: 10px; margin: 0px 6px 0px 20px;">
                                <table class="no-padding-table">
                                    <tr>
                                        <td rowspan="4">
                                            <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" width="48px" height="48px">
                                        </td>
                                        <td>Ditandatangani secara elekronik oleh:</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div style="margin-bottom: 20px">
                                                @if ($draft->TtdText == 'PLT')
                                                    Plt. {!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText == 'PLH')
                                                    Plh. {!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'Atas_Nama')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br><{!! $draft->reviewer->role->RoleName !!},

                                                @elseif ($draft->TtdText2 == 'untuk_beliau')
                                                    a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                                                    <br>{!! $draft->sender->parentRole->RoleName !!},
                                                    <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                                                @else
                                                    {!! $draft->reviewer->role->RoleName !!},
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <?= $draft->Nama_ttd_konsep ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            {!! $draft->reviewer->Pangkat !!}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="clearfix"></div>
                    </section>
                </div>
            @endif
        </main>
    </body>
</html>
