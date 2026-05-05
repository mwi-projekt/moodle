#!/bin/bash
set -ue

BASE_PATH="${1:-../local-moodle}"
ROOT_DEST="$BASE_PATH/server/moodle"
echo "Using Moodle root: $ROOT_DEST"

[[ -d "$ROOT_DEST" ]] || {
  echo "Destination root '$ROOT_DEST' does not exist."
  exit 1
}

copy_plugin() {
  local src="$1"
  local subdir="$2"
  local name="${3:-$1}"

  local dest="$ROOT_DEST/$subdir/$name"
  echo "Processing '$src' -> '$dest'"

  [[ -d "$src" ]] || {
    echo "  Skipping: source folder '$src' does not exist."
    return
  }

  rm -rf "$dest"
  mkdir -p "$ROOT_DEST/$subdir"
  cp -r "$src" "$dest"

  echo "  Done."
}

copy_plugin "dataform_plugin" "mod" "dataform"
copy_plugin "io_plugin" "mod" "dhbwio"
copy_plugin "zuweisungsmatrix" "local"
