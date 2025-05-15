<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    /**
     * Display the portfolio profile page.
     *
     * @return \Illuminate\View\View
     */
    public function show()
    {
        // You can add data to pass to the view here if needed
        return view('portfolio.profile');
    }
}
