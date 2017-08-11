package main

import (
  "log"
)

func handleError(err error, message string) {
  if message == "" {
    message = "Error making API call"
  }
  if err != nil {
    log.Fatalf(message + ": %v", err.Error())
  }
}
