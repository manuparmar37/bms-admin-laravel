<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MoviesController extends Controller
{
    public function getMovies2(Request $request) {
        $query = 'SELECT distinct(m.id) as id, m.name as name, l.name as language, l.id as language_id from movies m join languages l on m.language_id = l.id';
        $filterOnLocationIds = !empty($request->location_ids);
        $filterOnLanguageIds = !empty($request->language_ids);
        $filterOnGenreIds = !empty($request->genre_ids);
        if($filterOnGenreIds) {
            $query .= ' join movie_genres as mg on mg.movie_id = m.id and mg.genre_id in (' . $request->genre_ids . ')';
        }
        if($filterOnLocationIds) {
            $query .= ' join movie_theatres as mt on mt.movie_id = m.id join theatres as t on mt.theatre_id = t.id and location_id in (' . $request->location_ids . ')';
        }
        if($filterOnLanguageIds) {
            $query .= ' where language_id in (' . $request->language_ids . ')';
        }
        $query .= ' limit 20';
        // return $query;
        $res = \DB::select($query);
        foreach($res as $r) {
            $theatre_query = 'SELECT t.name, t.price, t.id, t.location_id from theatres as t join movie_theatres as mt on t.id = mt.theatre_id where mt.movie_id = ' . $r->id;
            $cast_query = 'SELECT * from casts as c join movie_casts as mc on c.id = mc.cast_id where mc.movie_id = ' . $r->id;
            $genre_query = 'SELECT * from genres as g join movie_genres as mg on g.id = mg.genre_id where mg.movie_id = ' . $r->id;
            $theatres = \DB::select($theatre_query);
            foreach($theatres as $t) {
                $location = \DB::select('SELECT * from locations where id = ' . $t->location_id);
                $timings = array_map(function($obj) {return $obj->timing;},\DB::select('SELECT timing from theatre_timings where theatre_id = ' . $t->id));
                $t->timings = $timings;
                $t->location = $location[0];
            }
            $casts = \DB::select($cast_query);
            $genres = \DB::select($genre_query);
            $r->theatres = $theatres;
            $r->noOfLocations = count($theatres);
            $r->cast = $casts;
            $r->genre = $genres;
        }
        return $res;
    }

    public function getFilters() {
        $query1 = 'SELECT * from locations';
        $query2 = 'SELECT * from genres';
        $query3 = 'SELECT * from languages';
        $totalMovies = (\DB::select('select count(*) as count from movies'))[0]->count;
        return [
            'locations' => array_map(function($obj) {
                return [
                    'id' => $obj->id,
                    'name' => $obj->name,
                ];
            }, \DB::select($query1)),
            'genres' => array_map(function($obj) {
                return [
                    'id' => $obj->id,
                    'name' => $obj->name,
                ];
            }, \DB::select($query2)),
            'languages' => array_map(function($obj) {
                return [
                    'id' => $obj->id,
                    'name' => $obj->name,
                ];
            }, \DB::select($query3)),
            'total_movies' => $totalMovies
    ]   ;
    }
}
