<?php

return [

    // Do not change the array order
    'peoplePositionGroups' => [
        '1' => array(
            'GUBERNUR JAWA BARAT'
        ),
        '2' => array(
            'WAKIL GUBERNUR JAWA BARAT',
            'SEKRETARIS DAERAH PROVINSI JAWA BARAT',
            'ASISTEN',
            'KEPALA SEKRETARIAT KOMISI PEMILIHAN UMUM',
            'KEPALA BIRO',
            'KEPALA BALAI',
            'KEPALA CABANG',
            'KEPALA UPTD',
            'DIREKTUR RUMAH SAKIT'
        ),
        '3' => array(
            'KEPALA BADAN',
            'KEPALA PELAKSANA HARIAN BADAN',
            'SEKRETARIS DEWAN PERWAKILAN RAKYAT DAERAH',
            'KEPALA INSPEKTORAT',
            'INSPEKTUR DAERAH',
            'KEPALA DINAS',
            'KEPALA SATUAN POLISI PAMONG PRAJA'
        ),
        '4' => array(
            'SEKRETARIS DINAS',
            'SEKRETARIS INSPEKTORAT',
            'SEKRETARIS BADAN'
        ),
        '5' => array(
            'KEPALA BAGIAN',
            'KEPALA SUBBAGIAN',
            'KEPALA SUB BAGIAN',
            'KEPALA BIDANG',
            'KEPALA SUBBIDANG',
            'KEPALA SUB BIDANG',
            'KEPALA SEKSI',
            'KEPALA RUMAH',
            'INSPEKTUR PEMBANTU',
            'WAKIL DIREKTUR',
        )
    ],

    'sekdaRoleIdGroups' => [
        'XxJyPn38Yh.3',
        'XxJyPn38Yh.40'
    ],

    //receiver as on draft for direct sent to target (not forward to UK)
    'draftReceiverAsToTarget' => [
        'outboxnotadinas'       => 'to_notadinas',
        'outboxsprint'          => 'to_sprint',
        'outboxpengumuman'      => 'to_pengumuman',
        'outboxsuratizin'       => 'to_surat_izin_keluar',
        'outboxsprintgub'       => 'to_sprint_gub',
        'outboxrekomendasi'     => 'to_rekomendasi',
        'outboxsupertugas'      => 'to_super_tugas_keluar',
    ],

];
