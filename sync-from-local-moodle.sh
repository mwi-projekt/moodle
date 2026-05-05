#!/bin/bash
set -ue

BASE_PATH="${1:-../local-moodle}"
ROOT_SRC="$BASE_PATH/server/moodle"
echo "Using Moodle root: $ROOT_SRC"

[[ -d "$ROOT_SRC" ]] || {
  echo "Source root '$ROOT_SRC' does not exist."
  exit 1
}

sync_back() {
  local subdir="$1"
  local name="$2"
  local dest="$3"

  local src="$ROOT_SRC/$subdir/$name/"
  echo "Syncing '$src' -> '$dest'"

  [[ -d "$src" ]] || {
    echo "  Skipping: source folder '$src' does not exist."
    return
  }

  mkdir -p "$dest"
  rsync -a --delete "$src" "$dest/"
  echo "  Done."
}

sync_back "mod" "dataform" "dataform_plugin"
sync_back "mod" "dhbwio" "io_plugin"
sync_back "local" "zuweisungsmatrix" "zuweisungsmatrix"
