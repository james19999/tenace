<?php

namespace App\Support;

use Illuminate\Support\Str;

class DashboardChart
{
    public array $options;

    private array $datasets;

    public function __construct(array $options, array $data)
    {
        $this->options = $options;
        $this->options['chart_name'] = strtolower(Str::slug($options['chart_title'], '_'));

        $condition = $options['conditions'][0] ?? [];
        $this->datasets = [[
            'name' => $options['name'] ?? $options['chart_title'],
            'color' => $condition['color'] ?? '',
            'chart_color' => $options['chart_color'] ?? '',
            'fill' => $condition['fill'] ?? '',
            'data' => $data,
            'hidden' => $options['hidden'] ?? false,
            'stacked' => $options['stacked'] ?? false,
        ]];
    }

    public function renderHtml()
    {
        return view('laravelchart::html', ['options' => $this->options]);
    }

    public function renderJs()
    {
        return view('laravelchart::javascript', [
            'options' => $this->options,
            'datasets' => $this->datasets,
        ]);
    }

    public function renderChartJsLibrary(): string
    {
        return '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>';
    }
}
