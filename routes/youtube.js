var getSubtitles = require('youtube-captions-scraper').getSubtitles;
var express = require('express');
var router = express.Router();

/**
* GET users listing.
* yt?videoId=eDzGRT06er0&lang=en
**/
router.get('/', function(req, res, next) {
  // videoId=eDzGRT06er0, lang=en
  let {videoId, lang} = req.query

  if( !videoId ) {
    res.send({status: false, message:'videoId is required'})
  }
  if( !lang ) {
    res.send({status: false, message:'lang is required'})
  }
  console.log(videoId, lang, "videoId, lang")
  getSubtitles({
    videoID: videoId,
    lang: lang
  })
  .then(function(captions) {
    res.send({status:true, captions});
  });
});

module.exports = router;
