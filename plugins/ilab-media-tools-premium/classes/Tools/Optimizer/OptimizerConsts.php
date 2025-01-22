<?php

// Copyright (c) 2016 Interfacelab LLC. All rights reserved.
//
// Released under the GPLv3 license
// http://www.gnu.org/licenses/gpl-3.0.html
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

namespace MediaCloud\Plugin\Tools\Optimizer;

final class OptimizerConsts {
	const SKIPPED = 0;
	const DEFERRED = 1;
	const SUCCESS = 2;
	const SUCCESS_CLOUD = 3;

	const QUOTA_BYTES = 0;
	const QUOTA_API_CALLS = 1;
}