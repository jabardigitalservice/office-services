<?php

namespace App\Http\Traits;

use App\Exceptions\CustomException;
use App\Models\PassphraseSession;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

/**
 * Setup configuration for signature document
 */
trait SignatureTrait
{
    /**
     * setupConfigSignature
     *
     * @return array
     */
    public function setupConfigSignature()
    {
        $setup = [
            'nik' => (config('sikd.enable_sign_with_nik')) ? auth()->user()->NIK : config('sikd.signature_nik'),
            'url' => config('sikd.signature_url'),
            'auth' => config('sikd.signature_auth'),
            'cookies' => config('sikd.signature_cookies'),
        ];

        return $setup;
    }

    /**
     * checkUserSignature
     *
     * @param  array $setupConfig
     * @return string
     */
    public function checkUserSignature($setupConfig)
    {
        $checkUrl = $setupConfig['url'] . '/api/user/status/' . $setupConfig['nik'];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $setupConfig['auth'],
                'Cookie' => 'JSESSIONID=' . $setupConfig['cookies'],
            ])->get($checkUrl);

            return $response->body();
        } catch (\Throwable $th) {
            throw new CustomException('Gagal terhubung untuk pengecekan NIK ke API BSrE', $th->getMessage());
        }
    }

    /**
     * createPassphraseSessionLog
     *
     * @param  mixed $response
     * @return void
     */
    public function createPassphraseSessionLog($response, $id = null)
    {
        $passphraseSession = new PassphraseSession();
        $passphraseSession->nama_lengkap    = auth()->user()->PeopleName;
        $passphraseSession->jam_akses       = Carbon::now();
        $passphraseSession->keterangan      = 'Berhasil melakukan TTE dari mobile';
        $passphraseSession->log_desc        = 'OK';

        if ($response->status() != Response::HTTP_OK) {
            $bodyResponse = json_decode($response->body());
            $passphraseSession->keterangan      = 'Gagal melakukan TTE dari mobile';
            $passphraseSession->log_desc        = $bodyResponse->error . ' | File : ' . $id . ' | User : ' . auth()->user()->PeopleId;
        }

        $passphraseSession->save();

        return $passphraseSession;
    }
}
