<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestimonialRequest;
use App\Models\Testimonial;

class TestimonialController extends Controller
{
    public function index()
    {
        return Testimonial::ordered()->get();
    }

    public function store(TestimonialRequest $request)
    {
        return response()->json(Testimonial::create($request->validated()), 201);
    }

    public function show(Testimonial $testimonial)
    {
        return $testimonial;
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial)
    {
        $testimonial->update($request->validated());

        return $testimonial;
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return response()->noContent();
    }
}
