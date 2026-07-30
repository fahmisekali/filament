<?php

namespace App\Filament\Resources\Transactions\Schemas;
use App\Models\Item;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
class TransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(auth()->id()),
                Hidden::make('date')
                    ->default(now())->required(),
                Section::make('Payment')
                ->schema([
                    TextInput::make('pay_total')->prefix('Rp.')->numeric()->inlineLabel(),
                    TextInput::make('change')->prefix('Rp.')->numeric()->inlineLabel(),
            ]),
            Section::make('Cart')
            ->schema([
                Repeater::make('detail')->hiddenLabel()
                ->relationship()
                ->schema([
                    Select::make('item_id')
                    ->options(Item::all()->pluck('name','id'))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set, $get) {
                       $item = Item::find($state);
                       if($item){
                        $qty = (int) ($get('qty') ?? 1);
                        $set('price', $item->price);
                        $set('subtotal', $item->price * $qty);
                       }
                       
                       $total = collect($get('../../detail'))->sum('subtotal');
                       $set('../../total', $total);
                    }),
                    TextInput::make('qty')
                    ->numeric()
                    ->default(1)
                    ->reactive()
                    ->required()
                    ->afterStateUpdated(function ($state, $set, $get){
                        $item = Item::find($get('item_id'));
                        if($item){
                            $set('subtotal', $state * $item->price);
                        }

                        $total = collect($get('../../detail'))->sum('subtotal');
                        $set('../../total', $total);
                    }),
                    TextInput::make('subtotal')->prefix('Rp.')->numeric()->readOnly(),
                    Hidden::make('price'),
                ])->columns(3)
                ->addActionLabel('Add Item')
                ->live()
                ->deleteAction(
                    fn ($action) => $action->after(function(callable $get, callable $set){
                        $total = collect($get('detail'))->sum('subtotal');
                        $set('total', $total);
                    })
                ),
                TextInput::make('total')
                ->numeric()
                ->prefix('Rp.')
                ->inlineLabel()
                ->readOnly()
                ->required(),
            ]),
            ]);
    }
}