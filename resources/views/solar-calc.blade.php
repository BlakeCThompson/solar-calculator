@extends('layout.app')
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

    <body>
        <title>Laravel</title>
        <form action = "{{ route('solarSavings') }}" method="POST">
        <div><tag>What season is this?: </tag><select name="season">
                    <option value="w">Winter</option>
                    <option value="s">Summer</option>
                </select></div>
            <div><tag>Monthly kWh you produced: </tag><input name="kwh-produced"></div>
            <div><tag>kWh that you bought: </tag><input name="kwh-bought"></div>
            <div><tag>kWh that you pushed onto the grid: </tag><input name="kwh-pushed"></div>
            @csrf
            <div>
                <button type="submit" class="btn btn-primary">Find Savings</button>
            </div>
        </form>
    </body>
</html>
