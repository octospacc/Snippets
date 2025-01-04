<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:str="http://exslt.org/strings"
	xmlns:openSearch='http://a9.com/-/spec/opensearchrss/1.0/'
	xmlns:gd='http://schemas.google.com/g/2005'
	xmlns:thr='http://purl.org/syndication/thread/1.0'
	xmlns:georss='http://www.georss.org/georss'>
<xsl:output method="text" encoding="UTF-8" />
<xsl:template match="/feed">
<xsl:for-each select="entry[content/@type='html'][link/@rel='alternate'][not(thr:in-reply-to)]">
---
title: "<xsl:value-of select="title" />"
slug: <xsl:value-of select="str:replace(str:replace(link[@rel='alternate'][@type='text/html']/@href, 'https://', 'http://'), str:replace(/feed/link[@rel='alternate'][@type='text/html']/@href, 'https://', 'http://'), '')" />
date: <xsl:value-of select="published" />
type: <xsl:value-of select="str:replace(category[starts-with(@term, 'http://schemas.google.com/blogger/2008/kind#')]/@term, 'http://schemas.google.com/blogger/2008/kind#', '')" />
author: <xsl:value-of select="author/name" />
canonical_url: <xsl:value-of select="link[@rel='alternate'][@type='text/html']/@href" />
tags: <xsl:for-each select="category[not(@term='http://schemas.google.com/blogger/2008/kind#page' or @term='http://schemas.google.com/blogger/2008/kind#post')]">
  - <xsl:value-of select="./@term" />
</xsl:for-each>
---

<xsl:value-of select="content" />

<![CDATA[<!-- entry /-->]]>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>
