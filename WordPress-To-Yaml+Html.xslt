<?xml version="1.0" encoding="UTF-8" ?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/">
<xsl:output method="text" encoding="UTF-8" />
<xsl:template match="/rss/channel">
<xsl:for-each select="item[wp:post_type='post' or wp:post_type='page']">
---
title: "<xsl:value-of select="title" />"
slug: <xsl:value-of select="wp:post_name" />
date: <xsl:value-of select="wp:post_date" />
type: <xsl:value-of select="wp:post_type" />
status: <xsl:value-of select="wp:status" />
canonical_url: <xsl:value-of select="link" />
category: <xsl:value-of select="category[@domain='category']" /> <!-- <xsl:value-of select="category[@domain='category']/@nicename" /> -->
tags: <xsl:for-each select="category[@domain='post_tag']">
  - <xsl:value-of select="." /> <!-- <xsl:value-of select="./@nicename" /> -->
</xsl:for-each>
---

<xsl:value-of select="content:encoded" />

<![CDATA[<!-- wp:item /-->]]>
</xsl:for-each>
</xsl:template>
</xsl:stylesheet>
