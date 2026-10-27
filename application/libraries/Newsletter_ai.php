<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Newsletter_ai
 *
 * Appel non streamé de l'API Messages d'Anthropic, pour les traitements
 * purement textuels de la veille (révision d'un brouillon) et la page de
 * test de l'API.
 */
class Newsletter_ai {

    const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * @param string $api_key
     * @param string $model
     * @param string $user        Message utilisateur
     * @param string $system      Consignes système (optionnel)
     * @param int    $max_tokens
     * @param int    $timeout     Secondes
     * @return array  ok, text, model, input_tokens, output_tokens, stop_reason | ok=false, error
     */
    public function message($api_key, $model, $user, $system = '', $max_tokens = 1024, $timeout = 60) {
        $payload = array(
            'model'      => $model,
            'max_tokens' => (int) $max_tokens,
            'messages'   => array(array('role' => 'user', 'content' => $user)),
        );
        if ($system !== '') {
            $payload['system'] = $system;
        }

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array(
                'x-api-key: ' . $api_key,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ),
            CURLOPT_TIMEOUT        => (int) $timeout,
        ));

        $response   = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            return array('ok' => false, 'error' => 'cURL : ' . $curl_error);
        }

        $data = json_decode($response, true);
        if ($http_code !== 200) {
            $err = isset($data['error']['message']) ? $data['error']['message'] : $response;
            return array('ok' => false, 'error' => "HTTP {$http_code} : {$err}");
        }

        $text = '';
        foreach ($data['content'] ?? array() as $block) {
            if (($block['type'] ?? '') === 'text') $text .= $block['text'];
        }
        return array(
            'ok'            => true,
            'text'          => $text,
            'model'         => $data['model'] ?? $model,
            'input_tokens'  => $data['usage']['input_tokens'] ?? 0,
            'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            'stop_reason'   => $data['stop_reason'] ?? '',
        );
    }
}
