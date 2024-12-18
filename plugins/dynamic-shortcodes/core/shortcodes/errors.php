<?php

// SPDX-FileCopyrightText: 2022-2024 Ovation S.r.l. <help@dynamic.ooo>
// SPDX-License-Identifier: LicenseRef-GPL-3.0-with-dynamicooo-additional-terms

namespace DynamicShortcodes\Core\Shortcodes;

//phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound
class ParseError extends \Exception {}
class EvaluationError extends \Exception {}
class PermissionsError extends EvaluationError {}
