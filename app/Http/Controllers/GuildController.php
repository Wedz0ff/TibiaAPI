<?php

namespace App\Http\Controllers;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

class GuildController extends Controller
{
    /**
     * Return the guild information
     *
     * @param  string $name
     * @return \Illuminate\Http\Response
     */
    public function show($name)
    {

        $name = urldecode(str_replace(" ", "+", $name));
        $guild = [];
        $client = new Client();

        $guzzleClient = new \GuzzleHttp\Client(array(
            'timeout' => 90,
            'verify' => false,
        ));

        $client->setClient($guzzleClient);

        try {
            $crawler = $client->request('GET', 'https://www.tibia.com/community/?subtopic=guilds&page=view&GuildName=' . $name);

            $guild['Information']["Summary"] = preg_replace("/\r|\n/", " ", $crawler->filter('#GuildInformationContainer')->text());

            $rows = array();
            $tr_elements = $crawler->filter('#guilds .Table3 .TableContentContainer');
            foreach ($tr_elements as $i => $content) {
                $tds = array();
                $crawler = new Crawler($content);
                foreach ($crawler->filter('td') as $i => $node) {
                    $tds[] = $node->nodeValue;
                }
                $rows[] = $tds;
            }
            $members = array_chunk($rows[0], 6);
            array_shift($members);
            $rank = "";
            $levelSum = 0;
            $onlineCount = 0;
            foreach ($members as $m) {
                if (strlen($m[0]) !== 2) {
                    $rank = $m[0];
                }
                $guild["Members"][$rank][] = ["Name" => $m[1], "Vocation" => $m[2], "Level" => $m[3], "Join Date" => $m[4], "Status" => $m[5]];
                $levelSum += intval($m[3]);
                if ($m[5] == "online")
                    $onlineCount += 1;
            }
            $guild["Information"]["Average Level"] = round($levelSum / count($members));
            $guild["Information"]["Members Online"] = $onlineCount;
            return response()->json($guild, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Guzzle\Http\Exception\BadResponseException $e) {

            return response()->json(["error" => "Unknown error."], 504);
        }
    }
}
