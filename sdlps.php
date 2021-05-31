<!DOCTYPE html>

<html lang="ja">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>災害地名迷信予測 -SDLPS-</title>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" href="saimei.png">
  <meta name="format-detection" content="telephone=no,email=no,address=no">
  <meta name="author" content="nkgw-marronnier">
  <meta name="description" content="地名から災害を予測し, 科学的に証明されているデータと比較し, その結果から検索者の防災意識を向上させる娯楽的防災啓発サービスである. ">
  <meta name="keywords" content="迷信,地名,災害地名,海抜,地震,土砂災害,災害予測">
  <meta name="robots" content="noindex,nofollow,noarchive,noimageindex,notranslate">
</head>

<body>
  <header>
    <div class="logo">
      <h1><a href="sdlps.php">災害地名迷信予測 -SDLPS-</a></h1>
    </div>
  </header>
  <div class="main">
    <div class="system_midashi">
      地名から災害を予測する新感覚迷信サイト
    </div>
    <div class="system_kaisetsu">
      ＊検索の手引き＊<br>
      「○○県○○市○○」の形式で入力してください. <br>
      郵便番号は入力しないでください. <br>
      例：千葉県習志野市津田沼<br>
      番地まで入力するとエラーになる可能性があります. <br>
    </div>

    <?php
    $chimeirei = []; //連想される地名を格納
    $saigai = []; //連想される災害を格納
    $kaisetsu = []; //地名と災害の関係性の解説を格納

    //入力された住所を変数に格納する
    if (isset($_GET['chimei'])) {
      $chimei = $_GET['chimei'];
    } else {
      //システム評価時にフォーム入力の時間を削減するために初期値は乱数で決定
      $rand = rand(0, 9);
      if ($rand == 0) {
        $chimei = "千葉県習志野市津田沼";
      } else if ($rand == 1) {
        $chimei = "愛知県豊橋市梅藪町";
      } else if ($rand == 2) {
        $chimei = "岐阜県高山市奥飛騨温泉郷平湯";
      } else if ($rand == 3) {
        $chimei = "北海道北斗市桜岱";
      } else if ($rand == 4) {
        $chimei = "兵庫県西宮市上大市";
      } else if ($rand == 5) {
        $chimei = "沖縄県北中城村瑞慶覧";
      } else if ($rand == 6) {
        $chimei = "長崎県佐世保市小佐々町臼ノ浦";
      } else if ($rand == 7) {
        $chimei = "島根県隠岐郡西ノ島町浦郷";
      } else if ($rand == 8) {
        $chimei = "茨城県古河市女沼";
      } else {
        $chimei = "宮城県石巻市鮎川浜";
      }
      $_GET['chimei'] = $chimei;
    }
    ?>

    <div class="form">
      <form method="GET" action="sdlps.php">
        <div class="form_setsumei">
          調べる住所
        </div>
        <input type="text" name="chimei" size="40" value="<?php echo $_GET['chimei']; ?>" minlength="6" maxlength="25" required>
        <input type="submit" value="検索" class="kensaku">
      </form>
    </div>

    <?php
    /*WebAPIを用いて必要な情報を取得する*/
    //既に取得してあるYahoo!APIのキー
    $id = "";

    /*
    住所から迷信上で災害の危険があるか否かを
    取得するリクエストURLを生成する
    */
    /*
    崩壊地名 API
    © LivLog llc. All rights reserved.
    https://www.livlog.xyz/houkaichimei/
    */
    $req1 = "https://livlog.xyz/webapi/collapse/check?query=" . $chimei;

    //崩壊地名APIを用いてJSONデータをダウンロードする
    $houkai_json = @file_get_contents($req1);

    //不正な入力やWebAPI群の停止を判定. エラー出力を防止
    if ($houkai_json) {
      $houkai_arr = json_decode($houkai_json, true);
      $json_count = count($houkai_arr["data"]);
      $result = $houkai_arr["result"];

      //JSONデータから目的とするデータを抽出する
      for ($i = $json_count - 1; $i >= 0; $i--) {
        $chimeirei[$i] = $houkai_arr["data"][$i]["chimeirei"];
        $kaisetsu[$i] = $houkai_arr["data"][$i]["kaisetsu"];
        $saigai[$i] = $houkai_arr["data"][$i]["saigai"];
        $yomi[$i] = $houkai_arr["data"][$i]["yomi"];
      }

      //災害例を配列から正規表現で抽出し, 判定材料に加工
      $saigai_suigai = preg_grep('/水害/', $saigai);
      $saigai_houkai = preg_grep('/崩壊/', $saigai);
      $saigai_ekijouka = preg_grep('/液状化/', $saigai);
    } else {
      $result = "ERROR";
    }

    //デバッグ用
    //print_r($arr);

    /*
    コンテンツジオコーダAPI
    Copyright (C) 2020 Yahoo Japan Corporation. All Rights Reserved.
    https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/contentsgeocoder.html
    */
    //住所から経度緯度を取得
    $req2 = "https://map.yahooapis.jp/geocode/cont/V1/contentsGeoCoder?appid=" . $id . "&query=" . $chimei . "&category=address";
    $idokeido_xml = @simplexml_load_file($req2);

    //WebAPIが動作しているかの確認. 停止によるエラー出力を抑止
    if ($idokeido_xml) {
      $idokeido_hantei = $idokeido_xml->attributes()->totalResultsReturned;

      if ($idokeido_hantei != 0) {
        $idokeido = $idokeido_xml->Feature->Geometry->Coordinates;

        //緯度経度から正式な住所並びに詳細情報を取得
        $jusyo = $idokeido_xml->Feature->Property->Address;
        $jusyo_kana = $idokeido_xml->Feature->Property->AddressKana;
      }
    } else {
      $idokeido_hantei = 0;
    }

    //デバッグ用
    //print_r($idokeido_xml);

    /*
    標高API
    Copyright (C) 2020 Yahoo Japan Corporation. All Rights Reserved.
    https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/altitude.html
    */
    //緯度経度から海抜を取得
    if ($idokeido_hantei != 0) {
      $idokeido_bunkatsu = explode(",", $idokeido);
      $ido = $idokeido_bunkatsu[1];
      $keido = $idokeido_bunkatsu[0];
      $req3 = "https://map.yahooapis.jp/alt/V1/getAltitude?appid=" . $id . "&coordinates=" . $idokeido;
      $kaibatsu_xml = @simplexml_load_file($req3);
      //XMLにデータが存在することを確認
      if ($kaibatsu_xml) {
        $kaibatsu = $kaibatsu_xml->Feature->Property->Altitude;
      } else {
        $kaibatsu = "ERROR";
      }
    } else {
      $kaibatsu = "ERROR";
    }

    //デバッグ用
    //print_r($kaibatsu_xml);

    //レスポンス速度が30sec以上と遅いため却下
    /*
    地点別浸水シミュレーション検索システム(浸水ナビ)
    Copyright. Ministry of Land, Infrastructure, Transport and Tourism of Japan. All Rights Reserved. 
    https://suiboumap.gsi.go.jp/
    */
    /*
    //検索地点周辺の河川情報を取得
    if($idokeido_hantei != 0){
      $req6 = "http://suiboumap.gsi.go.jp/shinsuimap/api/public/GetMaxArriveFromLatlon?lon=".$keido."&lat=".$ido;          $kasen_json = file_get_contents($req6);
      $kasen_arr = json_decode($kasen_json);

      //デバッグ用
      print_r($kasen_arr);
    }
    */

    /*
    メッシュ別被害地震検索API
    Copyright © 2011 国立研究開発法人 防災科学技術研究所
    http://www.j-shis.bosai.go.jp/api-fltsearch-mesh
    */
    //検索地点周辺の震度5弱以上に見舞われる原因となる断層・予想Mを取得
    if ($result == "Success") {
      if ($idokeido_hantei != 0) {
        $req6 = "http://www.j-shis.bosai.go.jp/map/api/fltsearch?position=" . $idokeido . "&epsg=4301&mode=C&version=Y2019&case=MAX&period=P_T30&format=json&ijma=45";

        $dansou_json = @file_get_contents($req6);

        //データが存在するか否かの確認
        if ($dansou_json) {
          $dansou_hantei = 0;
          $dansou_arr = json_decode($dansou_json, true);
          $dansou_count = count($dansou_arr["Fault"]);

          if ($dansou_count >= 1) {
            for ($i = $dansou_count - 1; $i >= 0; $i--) {
              //地層名
              $dansou_name[$i] = $dansou_arr["Fault"][$i]["ltename"];
              //予想されるマグニチュード
              $dansou_M[$i] = $dansou_arr["Fault"][$i]["magnitude"];
              //地震グループ(断層型, 海溝型など)
              $dansou_group[$i] = $dansou_arr["Fault"][$i]["eqgroup"];
            }
          }
          //デバッグ用
          //print_r($dansou_arr);
        } else {
          $dansou_hantei = "ERROR";
        }
      }
    }

    /*
    長期間平均ハザード情報提供API
    Copyright © 2011 国立研究開発法人 防災科学技術研究所
    http://www.j-shis.bosai.go.jp/api-avghzd-meshinfo
    */
    //再現期間1万年相当の予想される震度
    /*
    1万年に一度であって「今から1万年後」ではないし, 
    要因となる断層は数多に存在する
    再現期間1万年の場合は, ほぼ全ての海溝型地震と主要活断層帯の
    地震の震度を予測することができる
    */
    if ($idokeido_hantei != 0) {
      $req7 = "http://www.j-shis.bosai.go.jp/map/api/avghzd/V7/meshinfo.geojson?position=" . $idokeido . "&epsg=4301";
      $shindo_json = @file_get_contents($req7);
      //JSONにデータが存在することを確認
      if ($shindo_json) {
        $shindo_arr = json_decode($shindo_json, true);
        //再現期間1万年相当の震度を指定
        $shindo_max = $shindo_arr["features"][0]["properties"]["A010K_SI"];
      } else {
        $shindo_max = "ERROR";
      }
    }

    //デバッグ用
    //print_r($shindo_arr);
    //echo $shindo_max;

    /*
    地すべり地形情報提供API
    Copyright © 2011 国立研究開発法人 防災科学技術研究所
    http://www.j-shis.bosai.go.jp/api-landslide-iscontaining
    */
    //土砂災害判定
    if ($idokeido_hantei != 0) {
      $req5 = "http://www.j-shis.bosai.go.jp/map/api/landslide/isContaining.json?position=" . $keido . "," . $ido . "&epsg=4301";
      $dosya_json = @file_get_contents($req5);

      //JSONにデータが存在することを確認
      if ($dosya_json) {
        $dosya_arr = json_decode($dosya_json);

        //デバッグ用
        //print_r($dosya_arr);

        $dosya = $dosya_arr->isContaining;
        $dosyamap = $dosya_arr->metaData->url;
      } else {
        $dosya = "ERROR";
      }

      //デバッグ用
      /*
      print_r($saigai_suigai);
      print_r($saigai_houkai);
      print_r($saigai_ekijouka);
      */
    } else {
      $dosya = "ERROR";
    }


    /*簡易的な検索結果表示部分*/
    echo "<div class='system_komidashi'>検索結果</div>";

    echo "<div class='kekka'>";
    echo "<div class='kekka_naibu'>・地名から予想される災害(非科学)</div>";

    if ($result == "Success") {
      //JSONデータが存在することを確認
      if ($houkai_json === false) {
        //JSONにデータが存在しなかった場合(WebAPI停止などで)
        echo "<img class='result_img' src='result/error.png' alt='不正'>";
      } else {
        if ($saigai_suigai != null) {
          echo "<img class='result_img' src='result/suigai.png' alt='水害'>";
        }
        if ($saigai_houkai != null) {
          echo "<img class='result_img' src='result/houkai.png' alt='崩壊'>";
        }
        if ($saigai_ekijouka != null) {
          echo "<img class='result_img' src='result/ekijouka.png' alt='液状化'>";
        }
        if ($saigai_suigai == null && $saigai_houkai == null && $saigai_ekijouka == null) {
          echo "<img class='result_img' src='result/none.png' alt='災害無し'>";
        }
      }
    } else {
      //JSONにデータが存在しなかった場合(WebAPI停止などで)
      echo "<img class='result_img' src='result/error.png' alt='不正'>";
    }

    echo "<div class='kekka_naibu'>・海抜と土砂災害と最大震度(科学)</div>";

    //海抜判定結果を画像で表示
    if ($result == "Success" || $idokeido_hantei == 0) {
      //JSON, XML群にエラーがないことを確認
      if ($kaibatsu == "ERROR" || $houkai_json === false) {
        //JSON, XMLにデータが存在しなかった場合(WebAPI停止などで)
        echo "<img class='result_img' src='result/error.png' alt='不正'>";
      } else if ($kaibatsu < 15) {
        echo "<img class='result_img' src='result/15m.png' alt='海抜15m未満'>";
      } else if ($kaibatsu < 30) {
        echo "<img class='result_img' src='result/15mup.png' alt='海抜15m以上'>";
      } else {
        echo "<img class='result_img' src='result/30mup.png' alt='海抜30m以上'>";
      }
    } else {
      //JSON, XMLにデータが存在しなかった場合(WebAPI停止などで)
      echo "<img class='result_img' src='result/error.png' alt='不正'>";
    }

    //土砂災害警戒の有無を画像で表示(JSONデータがある場合のみ)
    if ($result == "Success") {
      //JSONにデータが存在しなかった場合(WebAPI停止などで)
      if ($idokeido_hantei != 0 && $dosya_json) {
        if ($dosya != 0) {
          echo "<img class='result_img' src='result/dosya.png' alt='土砂災害有り'>";
        } else {
          echo "<img class='result_img' src='result/none.png' alt='土砂災害無し'>";
        }
      } else {
        echo "<img class='result_img' src='result/error.png' alt='不正'>";
      }
    } else {
      //JSONにデータが存在しなかった場合(WebAPI停止などで)
      echo "<img class='result_img' src='result/error.png' alt='不正'>";
    }

    //予想される最大震度(科学的根拠に基づいた)を画像で表示
    if ($result == "Success") {
      if ($idokeido_hantei != 0) {
        //JSONデータが存在しているかの確認
        if ($shindo_json) {
          //震度を場合分けで出力
          if ($shindo_max == "7") {
            echo "<img class='result_img' src='result/7.png' alt='震度7'>";
          } else if ($shindo_max == "6U") {
            echo "<img class='result_img' src='result/6k.png' alt='震度6強'>";
          } else if ($shindo_max == "6L") {
            echo "<img class='result_img' src='result/6j.png' alt='震度6弱'>";
          } else if ($shindo_max == "5U") {
            echo "<img class='result_img' src='result/5k.png' alt='震度5強'>";
          } else if ($shindo_max == "5L") {
            echo "<img class='result_img' src='result/5j.png' alt='震度5弱'>";
          } else {
            echo "<img class='result_img' src='result/4.png' alt='震度4以下'>";
          }
        } else {
          //地震データが存在しなかった場合
          echo "<img class='result_img' src='result/error.png' alt='不正'>";
        }
      } else {
        //JSONにデータが存在しなかった場合(WebAPI停止などで)
        echo "<img class='result_img' src='result/error.png' alt='不正'>";
      }
    } else {
      //JSONにデータが存在しなかった場合(WebAPI停止などで)
      echo "<img class='result_img' src='result/error.png' alt='不正'>";
    }

    echo "</div><div class='kaisetsu_back'>";

    //不正表示の理由解説
    echo "<div class='kaisetsu_if'>正しい住所を入力しても全てが不正, または一部のみ不正と<br>";
    echo "表示された場合, WebAPI群の停止・終了が考えられます. <br>";
    echo "暫くお待ちいただくか, 利用元クレジット欄のリンクから<br>";
    echo "WebAPI提供状態(停止・提供終了)を確認してください. </div></div>";


    /*データの詳細表示部分*/
    echo "<div class='system_komidashi'>詳細情報</div>";

    echo "<div class='syousai'>";
    echo "<div class='syousai_midashi'>";
    echo "検索地名から予想された災害<br></div>";

    //合致データとJSONデータがあれば表として出力
    if ($result == "Success" && $houkai_json) {
      if ($saigai == null) {
        //地名が迷信上で災害を意味しない場合
        echo "<div class='chimei_saiwai'>この地名は幸いにも</div><div class='chimei_meishin'>「迷信上」</div><div class='chimei_saiwai'>では<br>災害に関する地名ではありませんでした. </div><br>";
      } else {
        //地名が迷信上で災害を意味する場合を表として出力
        echo "<div class='table_chimei'>";
        echo "<table class='chimei_table'>";

        for ($i = $json_count - 1; $i >= 0; $i--) {
          echo "<tr><td class='chimei_rei'>";
          echo "地名例</td><td class='chimei_kaisetsu'>" . $chimeirei[$i] . "...(" . $yomi[$i] . ")</td></tr><tr><td class='chimei_rei'>";
          echo "災害予想</td><td class='chimei_kaisetsu'>" . $saigai[$i] . "</td></tr><tr><td class='chimei_rei'>";
          echo "理由・由来</td><td class='chimei_kaisetsu'>" . $kaisetsu[$i] . "</td>";
        }

        echo "</tr>\n";
        echo "</table>\n";
        echo "</div>";
      }
    } else {
      //JSONにデータが存在しなかった場合(WebAPI停止などで)
      echo "<div class='chimei_false'>該当する住所がありませんでした. <br>";
      echo "正しく住所を入力してください. <br>";
      echo "(例)千葉県習志野市鷺沼</div>";
    }

    echo "</div>";

    //緯度経度から逆引きで正しい地名を表示
    if ($result == "Success") {
      echo "<div class='syousai'>";
      echo "<div class='syousai_midashi'>";
      echo "検索された住所と詳細情報</div>";
      echo "<div class='jusyo_kana_setsumei'>";

      //XMLにデータが存在しているかの確認
      if ($idokeido_hantei != 0 && $idokeido_xml !== true) {
        echo $jusyo_kana . "</div><div class='jusyo_setsumei'>" . $jusyo . "</div>";
      } else {
        //XMLにデータが存在しなかった場合(WebAPI停止などで)
        echo "<div class='idokeido_setsumei'>Not Found.</div></div>";
      }
      echo "</div>";
    }

    //緯度経度表示
    if ($result == "Success") {
      echo "<div class='syousai'>";
      echo "<div class='syousai_midashi'>";
      echo "検索住所から求めた経度・緯度</div>";

      echo "<div class='idokeido_setsumei'>";

      //XMLにデータが存在しているかの確認
      if ($idokeido_hantei != 0 && $idokeido_xml !== true) {
        echo "経度：　" . $keido . "<br>緯度：　" . $ido . "</div>";
      } else {
        //XMLにデータが存在しなかった場合(WebAPI停止などで)
        echo "Not Found</div>";
      }
      echo "</div>";
    }

    //海抜表示
    if ($result == "Success") {
      echo "<div class='syousai'>";
      echo "<div class='syousai_midashi'>";
      echo "緯度経度から求めた地点代表海抜</div>";

      echo "<div class='kaibatsu_setsumei'>";
      //XMLにデータが存在しているかの確認
      if ($idokeido_hantei != 0 && $kaibatsu_xml) {
        if ($kaibatsu < 40) {
          echo "<div class='dosya_kiken'>" . $kaibatsu . "m</div>";
          echo "<div class='dansou_kaisetsu'>(東日本大震災の最大遡上高である40mを基準)</div></div>";
        } else if ($kaibatsu >= 40) {
          echo $kaibatsu . "M</div>";
        } else {
          echo $kaibatsu . "</div>";
        }
      } else {
        //JSONにデータが存在しなかった場合(WebAPI停止などで)
        echo "Not Found</div>";
      }
      echo "</div>";
    }

    //土砂災害判定結果出力
    if ($result == "Success") {
      echo "<div class='syousai'>";
      echo "<div class='syousai_midashi'>";
      echo "検索地点周辺の土砂災害の危険性</div>";

      echo "<div class='dosya_setsumei'>";

      //JSONにデータが存在しているかの確認
      if ($idokeido_hantei != 0 && $dosya_json) {
        if ($dosya != 0) {
          echo "<div class='dosya_kiken'>有</div></div>";
        } else {
          echo "無し</div>";
        }

        echo "<a class='dosya_url' href='" . $dosyamap . "' target='_blank' rel='noopener noreferrer'>検索地点周辺の防災関連地図(J-SHIS MAP)</a>";
      } else {
        //JSONにデータが存在しなかった場合(WebAPI停止などで)
        echo "Not Found</div>";
      }
      echo "</div>";
    }

    //指定された地点付近の最大震度・断層数を表示
    if ($result == "Success") {
      echo "<div class='syousai'>";
      echo "<div class='syousai_midashi'>";
      echo "検索地点周辺の最大震度・断層数</div>";

      if ($idokeido_hantei != 0) {
        echo "<div class='shindo_setsumei'>最大震度：</div>";
        //JSONにデータが存在しているかの確認
        if ($shindo_json) {
          if ($shindo_max == "7") {
            echo "<div class='shindo_7'>7</div>";
          } else if ($shindo_max == "6U") {
            echo "<div class='shindo_6k'>6強</div>";
          } else if ($shindo_max == "6L") {
            echo "<div class='shindo_6j'>6弱</div>";
          } else if ($shindo_max == "5U") {
            echo "<div class='shindo_5k'>5強</div>";
          } else if ($shindo_max == "5L") {
            echo "<div class='shindo_5j'>5弱</div>";
          } else {
            echo "<div class='shindo_4'>4以下</div>";
          }
          echo "<div class='dansou_kaisetsu'>(再現期間1万年相当の予想計測震度)</div>";
        } else {
          echo "<div class='shindo_4'>ERROR</div>";
        }

        if ($dansou_json) {
          if (max($dansou_M) + min($dansou_M) >= 0) {
            echo "<div class='dosya_kiken'>M" . max($dansou_M) . "</div>";
            echo "<div class='dansou_kaisetsu'>(気象庁マグニチュード ： M)</div>";
          } else {
            echo "<div class='dosya_kiken'>Mw" . -1 * min($dansou_M) . "</div>";
            echo "<div class='dansou_kaisetsu'>(モーメント・マグニチュード ： Mw)</div>";
          }
          echo "<div class='dosya_kiken'>" . $dansou_count . "個</div>";
          echo "<div class='dansou_kaisetsu'>(震度5弱以上の地震を発生させる可能性のある断層)</div>";
        } else {
          echo "<div class='dosya_setsumei'>無し</div>";
        }
      } else {
        //JSONにデータが存在しなかった場合(WebAPI停止などで)
        echo "<div class='dosya_setsumei'>Not Found</div>";
      }
      echo "</div>";
    }

    //指定された地点周辺の断層一覧表を出力
    if ($result == "Success") {
      if ($idokeido_hantei != 0) {
        echo "<div class='syousai'>";
        echo "<div class='syousai_midashi'>";
        echo "検索地点周辺の断層名一覧</div>";

        //JSONが読み込まれていれば表を出力
        if ($dansou_json) {
          echo "<details>";
          echo "<summary>クリックで一覧表を表示</summary>";
          echo "<ul><li>";
          echo "A ... 主　要　活　断　層</li><li>B ... そ の 他 の 活 断 層</li><li>C ... 海　溝　型　地　震</li>";
          echo "<li>0.0Mは気象庁M, -0.0MはMw</li></ul>";

          echo "<div class='table_dansou'>";
          echo "<table class='dansou_table'>";
          echo "<tr><th class='dansou_name'>断層名</th><th class='dansou_type'>種類</th><th class='dansou_M'>M/Mw</th></tr>";

          for ($i = $dansou_count - 1; $i >= 0; $i--) {
            echo "<tr><td class='dansou_data'>";
            echo $dansou_name[$i] . "</td><td class='dansou_data'>" . $dansou_group[$i] . "</td><td class='dansou_data'>" . $dansou_M[$i] . "</td>";
          }

          echo "</tr>\n";
          echo "</table>\n";
          echo "</div>";
          echo "</details></div>";
        } else {
          //JSONにデータが存在しなかった場合(WebAPI停止などで)
          echo "<div class='dosya_setsumei'>無し</div></div>";
        }
      }
    }

    /*
    気象情報API
    Copyright (C) 2020 Yahoo Japan Corporation. All Rights Reserved.
    https://developer.yahoo.co.jp/webapi/map/openlocalplatform/v1/weather.html
    */
    //指定された地名の天気を取得する
    if ($result == "Success") {
      if ($idokeido_hantei != 0) {
        $req4 = "https://map.yahooapis.jp/weather/V1/place?coordinates=" . $idokeido . "&appid=" . $id . "&output=xml&past=2&interval=10";

        /*
        天気データを繰り返し処理で抽出するため
        XMLをJSONに変換し, 配列へ格納する
        */
        $weather_xml = @simplexml_load_file($req4);

        echo "<div class='syousai'>";
        echo "<div class='syousai_midashi'>";
        echo "検索地点の直近短時間降水量<br></div>";

        //XMLが読み込めていれば出力
        if ($weather_xml) {
          $weather_json = json_encode($weather_xml, true);
          $weather_arr = json_decode($weather_json, true);
          $weather_count = count($weather_arr["Feature"]["Property"]["WeatherList"]["Weather"]);

          //デバッグ用
          //print_r($weather_arr);

          //天気情報格納用配列を宣言
          $weather_rain = [];
          $weather_date = [];

          //XMLから天気データを抽出する
          for ($i = $weather_count - 1; $i >= 0; $i--) {
            $weather_rain[$i] = $weather_arr["Feature"]["Property"]["WeatherList"]["Weather"][$i]["Rainfall"];
            $weather_date[$i] = $weather_arr["Feature"]["Property"]["WeatherList"]["Weather"][$i]["Date"];
          }

          //地名が存在すれば表として出力
          echo "<details>";
          echo "<summary>クリックで降水量を表示</summary>";
          echo "<ul><li>";
          echo "濃い青枠 ... 予想降水量</li><li>薄い青枠 ... 実測降水量</li></ul>";
          echo "<div class='table_kousui'>";
          echo "<table class='kousui_table'>";
          echo "<tr><th class='kousui_date'>日付・時間</th><th class='kousui_mm'>降水量(mm/h)</th></tr>";

          for ($i = $weather_count - 1; $i >= 0; $i--) {
            //配列を分割し, 見やすいように整える
            $subyear = substr($weather_date[$i], 0, 4);
            $submonth = substr($weather_date[$i], 4, 2);
            $subday = substr($weather_date[$i], 6, 2);
            $subhour = substr($weather_date[$i], 8, 2);
            $subminute = substr($weather_date[$i], 10, 2);
            //実測値予測値を判定して可視化
            if ($i >= $weather_count - 6) {
              echo "<tr><td class='kousui_data_yosoku'>";
              echo $subyear . "/" . $submonth . "/" . $subday . " " . $subhour . ":" . $subminute . "</td><td class='kousui_data_yosoku'>" . $weather_rain[$i] . "</td>";
            } else {
              echo "<tr><td class='kousui_data'>";
              echo $subyear . "/" . $submonth . "/" . $subday . " " . $subhour . ":" . $subminute . "</td><td class='kousui_data'>" . $weather_rain[$i] . "</td>";
            }
          }
          echo "</tr>\n";
          echo "</table>\n";
          echo "</div></details>";
        } else {
          //JSONにデータが存在しなかった場合(WebAPI停止などで)
          echo "<div class='dosya_setsumei'>Not Found</div>";
        }
        echo "</div>";
      }
    }

    /*このWebサービスの目的*/
    echo "<div class='mokuteki_back'><div class='syousai'>";
    echo "<div class='syousai_midashi'>このWebサービスの目的</div>";
    echo "<div class='mokuteki'>";
    echo "このWebサービスは地名から災害を予測するという<br>";
    echo "いわば迷信的で陰謀論的, 且つ娯楽的なものを<br>";
    echo "予測された災害が起きるであろう前提条件, <br>";
    echo "例えば海抜であったり土砂災害であったりなど<br>";
    echo "「迷信」と「現実」を比較した防災啓発サイトである. <br>";
    echo "昔の地名は実際に災厄を後世へ伝える例が多いが, <br>";
    echo "近年は市町村合併による変更や外国風の地名など<br>";
    echo "災害と関係ない場合が多い. このことからも安心せず, <br>";
    echo "防災学習, ハザードマップの確認を怠らないこと, <br>";
    echo "少しでも利用者の防災意識を向上させることが目的である. <br>";
    echo "</div></div></div>";

    /*WebAPI利用元クレジット表示*/
    echo "<div class='syousai'>";
    echo "<div class='syousai_midashi'>WebAPI・画像利用元クレジット</div>";
    echo "<a class='credit' href='https://developer.yahoo.co.jp/about'><img src='https://s.yimg.jp/images/yjdn/common/yjdn_attbtn1_250_34.gif' width='250' height='34' title='Webサービス by Yahoo! JAPAN' alt='Webサービス by Yahoo! JAPAN' border='0' style='margin:15px 15px 15px 15px' target='_blank' rel='noopener noreferrer'></a>";
    echo "<a class='credit_url' href='https://www.livlog.xyz/houkaichimei/' target='_blank' rel='noopener noreferrer'><br>崩壊地名API © LivLog llc. All rights reserved.</a>";
    echo "<a class='credit_url' href='http://www.j-shis.bosai.go.jp' target='_blank' rel='noopener noreferrer'><br>© 2011 国立研究開発法人 防災科学技術研究所</a>";
    echo "<a class='credit_url' href='http://www.saigaichousa-db-isad.jp/drsdb_photo/photoSearch.do' target='_blank' rel='noopener noreferrer'><br>© 財団法人消防科学総合センター</a>";
    echo "</div>";
    ?>
  </div>
  <footer>
    Copyright © nkgw-marronnier 2020 All rights reserved.
  </footer>

</body>

</html>