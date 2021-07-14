<?php

namespace SampleAPI\Data\ORM;

interface ORM extends
    SearchableDataSource,
    InsertableDataSource,
    UpdatableDataSource,
    DeletableDataSource
{
}