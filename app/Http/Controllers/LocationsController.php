<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LocationsController extends Controller
{
    public function checkConnection()
    {
        try {
            // Attempt a basic query to check the connection
            $minutes = 20000;

            $html = Cache::remember('selectOptions', $minutes, function () {
            // Retrieve data from the database (adjust the query based on your needs)
            $options = DB::connection('mongodb')->collection('locations')
                ->orderBy('country')
                ->orderBy('city')
                ->get();

            // Generate the select field HTML
            $selectHtml = '';
            foreach ($options as $option) {
                $selectHtml .= "<li><a class='dropdown-item' href=".'/home/'.$option['locId'].">".$option['country'].' '.$option['city']."</a></li>"."\n";
            }

            return $selectHtml;
        });
            // If the query was successful, the connection is working
            return view('home', compact('html'));
        } catch (\Exception $e) {
            // If there's an exception, the connection failed
            return response()->json(['success' => false, 'message' => 'MongoDB connection failed', 'error' => $e->getMessage()]);
        }
    }
    /*
    @params:
    latitude : latitude of the city selected.
    longitudde: longitude of the citu selected.
    @returns:
    Collection of the 5 nearest locations objects.
    */

    private function calculateNearestFive($latitude, $longitude) {
        $locations = DB::connection('mongodb')->collection('locations')->get();

        $locations = $locations->map(function ($location) use ($latitude, $longitude) {
            $location['distance'] = $this->haversineDistance($latitude, $longitude, $location['latitude'], $location['longitude']);
            return $location;
        });
        $locations = $locations->toArray();
        usort($locations, function ($a, $b) {
            return $a['distance'] - $b['distance'];
        });

        return array_slice($locations, 1, 5);
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; 

        $dlat = deg2rad($lat2 - $lat1);
        $dlon = deg2rad($lon2 - $lon1);

        $a = sin($dlat / 2) * sin($dlat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; 

        return $distance;
    }



    public function NearestFive($locId)
    {
        try {
            $minutes = 20000;
            $html = Cache::remember('selectOptions', $minutes, function () {
            $options = DB::connection('mongodb')->collection('locations')->get();
            $selectHtml = '';
            foreach ($options as $option) {
                $selectHtml .= "<li><a class='dropdown-item' href=".$option['locId'].">".$option['country'].' '.$option['city']."</a></li>"."\n";
            }
            $selectHtml .= '';
            return $selectHtml;
        });
            $selectedLocation = DB::connection('mongodb')->collection('locations')->get();
            foreach ($selectedLocation as $loc) {
                if($loc['locId']==$locId){
                    $selectedLocation = $loc;
                    break;
                }
            }
            $calculatedLocations = $this->calculateNearestFive($selectedLocation['latitude'],$selectedLocation['longitude']);
            return view('results', compact('html','calculatedLocations','selectedLocation'));
        } catch (\Exception $e) {
            // If there's an exception, the connection failed
            return response()->json(['success' => false, 'message' => 'MongoDB connection failed', 'error' => $e->getMessage()]);
        }
    }
    
}
