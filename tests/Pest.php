<?php

declare(strict_types=1);

use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Kurt\Modules\Licensing\Tests\ApiTestCase;
use Kurt\Modules\Licensing\Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

// The REST API kit tests need the module booted with `http.mode = api` so the
// `api/licensing/*` routes register. Kept in a sibling `tests/Api` suite so the
// binding does not overlap the headless-default `Feature` suite.
pest()->extend(ApiTestCase::class)->in('Api');

/*
|--------------------------------------------------------------------------
| Filament resource introspection helpers
|--------------------------------------------------------------------------
|
| Full Filament page rendering does not work under orchestra/testbench with
| the Filament v5 + Livewire v4 stack. These helpers assert the *structure*
| of a resource's form and table — proving each resource builds with the
| correct components/columns/actions for each Filament major — without
| rendering a Livewire page. A booted page instance is the schema/table
| container.
|
*/

if (! function_exists('formFilamentContainer')) {
    /**
     * v4/v5 use Filament\Schemas\Schema; v3 uses Filament\Forms\Form.
     *
     * @param  class-string  $pageClass
     */
    function formFilamentContainer(string $pageClass): object
    {
        $container = class_exists(Schema::class) ? Schema::class : Form::class;

        return $container::make(app($pageClass));
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function formFieldNames(string $resource, string $pageClass): array
    {
        $form = $resource::form(formFilamentContainer($pageClass));

        return array_keys($form->getFlatFields(withHidden: true));
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableColumnNames(string $resource, string $pageClass): array
    {
        $table = $resource::table(Table::make(app($pageClass)));

        return array_keys($table->getColumns());
    }

    /**
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableFilterNames(string $resource, string $pageClass): array
    {
        $table = $resource::table(Table::make(app($pageClass)));

        return array_keys($table->getFilters());
    }

    /**
     * @param  iterable<mixed>  $actions
     * @return array<int, string>
     */
    function flattenActionNames(iterable $actions): array
    {
        $names = [];

        foreach ($actions as $action) {
            if (is_object($action) && method_exists($action, 'getName')) {
                $names[] = $action->getName();
            }

            if (is_object($action) && method_exists($action, 'getActions')) {
                $names = array_merge($names, flattenActionNames($action->getActions()));
            }
        }

        return array_values(array_filter($names));
    }

    /**
     * Row action names. v5 keeps them under getFlatActions(); v4 stores them
     * in getRecordActions().
     *
     * @param  class-string  $resource
     * @param  class-string  $pageClass
     * @return array<int, string>
     */
    function tableActionNames(string $resource, string $pageClass): array
    {
        $table = $resource::table(Table::make(app($pageClass)));

        if (method_exists($table, 'getFlatActions')) {
            return flattenActionNames($table->getFlatActions());
        }

        return flattenActionNames($table->getRecordActions());
    }
}
