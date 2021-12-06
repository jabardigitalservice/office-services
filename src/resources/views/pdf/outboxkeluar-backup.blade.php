<style type="text/css">
    table.page_header {width: 1020px; border: none; background-color: #DDDDFF; border-bottom: solid 1mm #AAAADD; padding: 2mm }
    table.page_footer {width: 1020px; border: none;  border-top: white 1mm #; padding: 2mm}
    .tabel2 { border-collapse: collapse; }
    .tabel2 th, .tabel2 td { padding: 5px 5px; border: 1px solid #000; }
</style>

<page backtop="2mm" backbottom="14mm" backleft="1mm" backright="4mm">
    <!-- <page_header> -->
    <!-- Setting Header -->

    <!-- </page_header> -->
    <page_footer>
        <table class="page_footer">
            <tr>
            <td style="width: 5%; text-align: right">
                <!--     Halaman [[page_cu]]/[[page_nb]] -->
            </td>
            <td style="width: 50%; text-align: center">
                <div style="font-family: Arial; font-size: 9px; margin-left: 5px; text-align: center;">
                    Dokumen ini telah ditandatangani secara elektronik menggunakan sertifikat elektronik yang diterbitkan oleh
                    <br>
                    Balai Sertifikasi Elektronik (BSrE) Badan Siber dan Sandi Negara
                </div>
            </td>

            </tr>
        </table>
    </page_footer>
    <!-- Setting CSS Tabel data yang akan ditampilkan -->
    <div style="width: 100%; font-family: Arial; font-size: 12px; margin-left: 5px; margin-top: -96px;">
        <img style="width: 100%" src="{!! config('sikd.base_path_file') . 'kop/' . $header->Header !!}">
    </div>
    <div class="pos" id="_450:199" style="top:199;font-family:Arial; font-size:12px; margin-left:358">
        <p style=" id=" _15.8" font-family:Arial; font-size:12px;color:#000000">
        Tempat / Tanggal / Bulan / Tahun </p>
    </div>
    <div class="pos" id="_450:199" style="top:199; margin-left:359;font-family:Arial; font-size:12px;">
        <p style=" font-family:Arial; id=" _15.8" font-size:12px; color:#000000">
        Kepada</p>
    </div>
    <table cellpadding="17" cellspacing="1">
        <tbody>
            <tr>
                <br>
                <td align="normal" valign="top" width="352">
                    <div class="pos" id="_118:320" style="top:320;left:115">
                        <span id="_16.3" style=" font-family:Arial; font-size:12px; color:#000000">
                            Nomor &nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: &nbsp;&nbsp;&nbsp;.../{{ $draft->classification->ClCode; }}/{{ $draft->RoleCode; }}
                        </span>
                    </div>
                    <div class="pos" id="_118:320" style="top:320;left:115">
                        <span id="_16.3" style=" font-family:Arial; font-size:12px; color:#000000">
                            Sifat
                        </span>
                        <span id="_16.3" style=" font-family:Arial; font-size:12px;margin-left:35px; color:#000000">
                            &nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;{!! $draft->classified->SifatName; !!}
                        </span>
                    </div>
                    <div class="pos" id="_118:320" style="top:320;left:115">
                        <span id="_16.3" style=" font-family:Arial; font-size:12px; color:#000000">
                            Lampiran
                        </span>
                        <span id="_16.3" style=" font-family:Arial; font-size:12px;margin-left:9; color:#000000">
                            &nbsp;&nbsp;:&nbsp;&nbsp;&nbsp;&nbsp;{!! $draft->Jumlah . ' ' . $draft->measureUnit->MeasureUnitName; !!}
                        </span>
                    </div>
                    <div class="pos" id="_118:320" style="top:320;left:115; text-align: justify">
                        <span id="_16.3" style=" font-family:Arial; font-size:12px; color:#000000">
                            Hal
                        </span>
                        <span id="_16.3" style=" font-family:Arial; font-size:12px;margin-left:41; color:#000000">
                            &nbsp;&nbsp;:
                        </span>
                        <div style="width: 60%; font-family: Arial; font-size: 12px; margin-left: 11px;">
                            {!! $draft->Hal; !!}
                        </div>
                    </div>
                </td>

                <td align="normal" valign="top" width="180;font-family:Arial; font-size:12px;">Yth.&nbsp;
                    <div class="pos" id="_118:320" style="top:320;left:200;font-family:Arial; font-size:12px;">
                        {!! $draft->RoleId_To; !!}
                    </div>
                    <div class="pos" id="_118:320" style="top:320;margin-left:22;font-family:Arial;font-size:12px; ">di</div>
                    <div class="pos" id="_118:320" style="top:320;margin-left:35; margin-right:15px; font-family:Arial;font-size:12px;">{!! $draft->Alamat; !!}</div>
                </td>
            </tr>
        </tbody>
    </table>
    <br><br>
    <div style="border: 1px solid #000000; margin-right:-5px ;margin-left:75; line-height: 17px; font-family: Arial; font-size: 12px; text-align: justify;">
        {!! $draft->Konten; !!}
    </div>
    <br>
    <div style="width: 55%; font-family: Arial; font-size: 12px; margin-left: 281px; margin-top:-10px; ">
        @if ($draft->TtdText == 'PLT')
            <p style="float: right!important; margin-bottom: 0px; text-align: center; font-family: Arial; font-size: 12px;">Plt. {!! $draft->reviewer->role->RoleName !!},</p>
        @elseif ($draft->TtdText == 'PLH')
            <p style="float: right!important; margin-bottom: 0px; text-align: center; font-family: Arial; font-size: 12px;">Plh. {!! $draft->reviewer->role->RoleName !!},</p>
        @elseif ($draft->TtdText2 == 'Atas_Nama')
            <p style="float: right!important; margin-bottom: 0px; text-align: center; font-family: Arial; font-size: 12px;">a.n.&nbsp;<{!! $draft->reviewer->role->RoleName !!},
                <br>{!! $draft->reviewer->role->RoleName !!},</p>
        @elseif ($draft->TtdText2 == 'untuk_beliau')
            <p style="float: right!important; margin-bottom: 0px; text-align: center; font-family: Arial; font-size: 12px;">a.n.&nbsp;<{!! $draft->reviewer->role->RoleName !!},
                <br>{!! $draft->sender->parentRole->RoleName !!},
                <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},</p>
        @else
            <p style="float: right!important; margin-bottom: 0px; text-align: center; font-family: Arial; font-size: 12px;">{!! $draft->reviewer->role->RoleName !!},</p>
        @endif

        <p align="center">PEMERIKSA</p>

        <table style='border: 1; font-family: Arial; font-size: 10px; table-layout: fixed; overflow: hidden; width: 300; height: 200; margin-left: 29px;' cellspacing="0" >
            <tr>
                <td rowspan="5" >  <img src="{!! config('sikd.url') !!}/uploads/kosong.jpg" widht="20" height="50"></td>
                <td >Ditandatangani secara elekronik oleh:</td>
            </tr>
            <tr>
                <td style="overflow: hidden; width: 200px;">
                    @if ($draft->TtdText == 'PLT')
                        Plt. {!! $draft->reviewer->role->RoleName !!},

                    @elseif ($draft->TtdText == 'PLH')
                        Plh. {!! $draft->reviewer->role->RoleName !!},
                        <br>{!! $draft->reviewer->role->RoleName !!},

                    @elseif ($draft->TtdText2 == 'Atas_Nama')
                        a.n.&nbsp;{!! $draft->reviewer->role->RoleName !!},
                        <br><{!! $draft->reviewer->role->RoleName !!},

                    @elseif ($draft->TtdText2 == 'untuk_beliau')
                        a.n.&nbsp;<{!! $draft->reviewer->role->RoleName !!},
                        <br>{!! $draft->sender->parentRole->RoleName !!},
                        <br>u.b.<br>{!! $draft->reviewer->role->RoleName !!},

                    @else
                        {!! $draft->reviewer->role->RoleName !!},
                    @endif
                </td>
            </tr>

            <tr>
                <td><br></td>
                <td><br></td>
                <td><br></td>
            </tr>

            <tr>
                <td>
                    <p style="float: right!important;"><?= $draft->Nama_ttd_konsep ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    {!! $draft->reviewer->Pangkat !!}
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="font-family: Arial; font-size: 12px; line-height: 15px;">
        <tr>
            <td width="10%" valign="top">
                @php $greeting = 'Yth.'; @endphp
                Tembusan : <br>
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
                        @php $endGreeting = '.'; @endphp
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
    <br><br>
