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
                    <br>{!! $draft->createdBy->parentRole->RoleName !!},
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
                                <br>{!! $draft->createdBy->parentRole->RoleName !!},
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
