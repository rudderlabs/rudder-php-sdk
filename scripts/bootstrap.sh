#!/bin/bash

if ! which brew >/dev/null; then
  echo "homebrew is not available. Install it from http://brew.sh"
  exit 1
else
  echo "homebrew already installed"
fi

if ! which php >/dev/null; then
  echo "installing php."
  brew install php
else
  echo "php already installed"
fi

echo "installing pcov"
pecl -vvv install pcov

echo "all dependencies installed."
