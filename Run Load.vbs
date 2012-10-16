Call RunProcess()

Sub RunProcess()
  On Error Resume Next

  Dim URL1, URL2, URL3, objRequest

  Set objRequest = CreateObject("Microsoft.XMLHTTP")

  URL1 = "http://lab/d3_stats_git/tools/load.php?source=auto"
  URL2 = "http://lab/d3_stats_git/tools/get_item_images.php"
  URL3 = "http://lab/d3_stats_git/tools/get_skill_images.php"

  objRequest.open "GET", URL1, false
  objRequest.Send

  objRequest.open "GET", URL2, false
  objRequest.Send

  objRequest.open "GET", URL3, false
  objRequest.Send

  Set objRequest = Nothing
End Sub
