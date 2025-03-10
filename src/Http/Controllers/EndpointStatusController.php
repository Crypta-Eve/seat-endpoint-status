<?php

namespace CryptaEve\Seat\EndpointStatus\Http\Controllers;

use ReflectionClass;

use HaydenPierce\ClassFinder\ClassFinder;
use Seat\Services\Contracts\EsiClient;
use Seat\Web\Http\Controllers\Controller;
use Seat\Eseye\Exceptions\RequestFailedException;


class EndpointStatusController extends Controller 
{

    public function getStatusView()
    {

        $start = now();

        // each entry will be keyed by endpoint and method and have the following underlying keys
        // - latest_version = esi _latest version
        // - seat_version   = the version of the endpoint that SeAT is calling
        // - seat_status    = what the routeblocker has for this endpoint at the moment
        $endpoints = [];

        try {
            $client = app()->make(EsiClient::class);
            $client->setVersion('_latest');
            $call = $client->invoke('get', '/swagger.json');

            $response = $call->getBody();
            $paths = collect($response->paths);

            foreach($paths as $pathv => $val) {
                preg_match('/^\/(v\d+)\/([^\/]+)(\/.*)?\/?$/', $pathv, $matches);
                if (isset($matches[1]) && isset($matches[2])) {
                    // Extract version
                    $version = $matches[1]; // e.g. "v1"
                    
                    // Keep leading and trailing slashes and combine the first path segment with any remaining path
                    $path = '/' . $matches[2] . (isset($matches[3]) ? $matches[3] : '');
                    
                    
    
                    $endpoints[$path] = [];
                    $endpoints[$path]['latest_version'] = $version;
            
                } else {
                    continue;
                }
            }

       } catch (RequestFailedException $e) {
            logger()->error('unable to get _latest', ['error' => $e]);
            return redirect()->back()->with('error',  'Unable to request _latest/swagger.json');
       }

       $esiDone = now();

        $seatendpoints = [];

        // Now check all the SeAT Job classes
        $autoloadedClasses = ClassFinder::getClassesInNamespace('Seat\Eveapi\Jobs', ClassFinder::RECURSIVE_MODE);
        foreach($autoloadedClasses as $class) {
            if(is_subclass_of($class, 'Seat\Eveapi\Jobs\EsiBase') && !strpos($class, 'Abstract')){
                $reflection = new ReflectionClass($class);
                
                $filePath = $reflection->getFileName();
                if ($filePath) {                    
                    // Read the file content
                    $fileContent = file_get_contents($filePath);
                    
                    // Regular expression to extract the 'endpoint' and 'version' values
                    preg_match('/protected\s+\$endpoint\s*=\s*\'(.*?)\'/', $fileContent, $endpointMatches);
                    preg_match('/protected\s+\$version\s*=\s*\'(.*?)\'/', $fileContent, $versionMatches);
                    
                    // Extract the matched values
                    $endpointValue = isset($endpointMatches[1]) ? $endpointMatches[1] : 'Not found';
                    $versionValue = isset($versionMatches[1]) ? $versionMatches[1] : 'Not found';
                    // dd($endpointValue, $versionValue);
                    $endpoints[$endpointValue]['seat_version'] = $versionValue;
                    $seatendpoints[] = $endpointValue;
                } else {
                   continue;
                }
            }
        }

        $seatDone = now();

        foreach($seatendpoints as $se) {
            $cacheKey = 'esi-route-status:' . $se;
            $status = cache($cacheKey, 'Not Present');
            $endpoints[$se]['seat_status'] = $status;
        }

        $cacheDone = now();

        $esiTime = $esiDone->diffForHumans($start, false);
        $seatTime = $seatDone->diffForHumans($esiDone, false);
        $cacheTime = $cacheDone->diffForHumans($seatDone, false);



        return view("endpointstatus::status", compact('endpoints', 'esiTime', 'seatTime', 'cacheTime'));
    }

}
