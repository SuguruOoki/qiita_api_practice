<?php

/**
 * QiitaのAPIを扱う際の処理をラップしたクラス
 * PHPでAPIを叩く際には、file_get_contentsもあるが、
 * エラーハンドリングや、タイムアウトの時の処理を鑑みると
 * 使いにくいことが考えられるため、基本的にはcurlを使うこととした。
 * NOTE: 本来なら残さない方が良いコメントも残っているが、勉強のためなので、
 * 残している。
 */
class Qiita
{
    const POST_METHOD = 'POST';
    const GET_METHOD  = 'GET';
    const BASE_URL    = 'https://qiita.com';
    const ITEM_PATH   = '/api/v2/items/';

    private $configs;
    private $access_token;

    public function __construct() {
        $this->configs      = $this->getConfigs();
        $this->access_token = $this->configs['access_token'];
    }

    private function getConfigs() {
        $this->configs = include('config.php');
    }

    public function getPagesByTag(string $tag, int $limit_page) {
        
        $query = [
            'page'     => '1',
            'per_page' => (string)$limit_page
        ];
        
        $response = file_get_contents(
            self::BASE_URL . '/api/v2/tags/' . $tag . '/items?' .
            http_build_query($query)
        );
        
        $result = json_decode($response, true);
        
        return $result;
    }

    /**
     * 記事の情報を取得するメソッド。
     * 
     * @param string|array $item_id Qiitaの記事のID。複数の記事の情報を取得する場合にはarrayで指定する。
     * @return json Object QiitaのAPIのレスポンスをそのまま返す
     */
    public function getItem(string $item_id) {
        $curl = curl_init();
        $option = [
            CURLOPT_URL            => self::BASE_URL . self::ITEM_PATH . $item_id,
            CURLOPT_CUSTOMREQUEST  => self::GET_METHOD,
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_RETURNTRANSFER => true   // curl_execの結果を文字列で返す
        ];

        curl_setopt_array($curl, $option);

        $response = curl_exec($curl);
        $errno    = curl_errno($curl);
        $error    = curl_error($curl);
        $result   = json_decode($response, true);

        curl_close($curl);
        if (CURLE_OK !== $errno) {
            throw new RuntimeException($error, $errno);
        }
        return $result;
    }

    /**
     * 記事を投稿するメソッド。
     * TODO: 本来は複数記事投稿に対応すると良いが、まだ未実装。
     * もし複数記事投稿に対応する場合は引数自体を配列で持たせると良い。
     *
     * @param string  $title       記事のタイトルにする文字列
     * @param string  $contents    記事の内容とする文字列
     * @param boolean $private_flg 記事を限定公開にするかどうかのフラグ
     */
    private function postItem(string $title, string $contents, bool $private_flg = true) {

        $post_item = [
            'body'      => $contents,
            'coediting' => false, // QiitaTeamでのみ有効にできる共同編集用のフラグ
            'private'   => $private_flg,
            'title'     => $title,
            'tags'      => [
                             [
                                 'name' => 'PHP',
                                 'versions' => ["4.3.0",">="]
                             ],
                             [
                                 'name' => $title,
                             ]
                        ]
        ];

        $header = [
            'Authorization: Bearer ' . $this->access_token,  // 前準備で取得したtokenをヘッダに含める
            'Content-Type: application/json',
        ];

        $option = [
            CURLOPT_URL            => $this->base_url . $this->item_path,
            CURLOPT_CUSTOMREQUEST  => self::POST_METHOD,
            CURLOPT_SSL_VERIFYPEER => false, // 証明書の検証を行わない
            CURLOPT_RETURNTRANSFER => true, // curl_execの結果を文字列で返す
            CURLOPT_POSTFIELDS     => json_encode($post_item)
        ];
        curl_setopt_array($curl, $option);

        $response    = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE); 
        $header      = substr($response, 0, $header_size);
        $body        = substr($response, $header_size);
        $result      = json_decode($body, true); 
        curl_close($curl);

        return $result;
    }
}
$qiita = new Qiita();
$result = $qiita->getItem('bfdd533e5dacecd21a7a');

var_dump($result);
