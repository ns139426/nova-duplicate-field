<?php

namespace Jackabox\DuplicateField\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DuplicateController extends Controller
{
    /**
     * Duplicate a nova field and all of the relations defined.
     */
    public function duplicate(Request $request)
    {
        // Replicate the model
        $model = $request->model::where('id', $request->id)->first();

        if (!$model) {
            return [
                'status' => 404,
                'message' => 'No model found.',
                'destination' => url(config('nova.path') . '/resources/' . $request->resource . '/')
            ];
        }

        $newModel = $model->replicate();
        $newModel->push();

        if (isset($request->relations) && !empty($request->relations)) {
            // load the relations
            $model->load($request->relations);

            foreach ($model->getRelations() as $relation => $items) {
                // works for hasMany
                foreach ($items as $item) {
                    // clean up our models, remove the id and remove the appends
                    unset($item->id);
                    $item->setAppends([]);

                    // create a relation on the new model with the data.
                    $newModel->{$relation}()->create($item->toArray());
                }
            }
        }

        if (isset($request->maintain) && !empty($request->maintain)) {
            // load the fresh model with relations to maintain
            $model->$request->model::where('id', $request->id)->with($request->maintain)->first();

            foreach ($model->getRelations() as $relation => $items) {
                // works for hasMany
                foreach ($items as $item) {
                    $newModel->{$relation}()->attach($item);
                }
            }
        }

        // return response and redirect.
        return [
            'status' => 200,
            'message' => 'Done',
            'destination' => url(config('nova.path') . '/resources/' . $request->resource . '/' . $newModel->id)
        ];
    }
}
