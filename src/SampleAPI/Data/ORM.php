<?php

namespace SampleAPI\Data;

interface ORM extends
    SearchableDataSource,
    InsertableDataSource,
    UpdatableDataSource,
    DeletableDataSource
{
}